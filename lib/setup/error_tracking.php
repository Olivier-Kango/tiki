<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\ErrorListenerIntegration;
use Tiki\Errors;

/* This file handles reporting PHP errors to a remote service like glitchtip or sentry */

class ErrorTracking
{
    /** Set this to true when developing this tool.  It will ignore preferences and activate the code with a dummy DSN */
    private const LOCAL_DEBUG_MODE = false;

    private const STATE_DISABLED = 0;
    private const STATE_HOLD = 1;
    private const STATE_PUSH = 2;

    /**
     * Long-running commands (scheduler:run, tracker sync) can raise the same warning once per row.
     * Each Sentry event keeps a stack trace, so an unbounded queue retains those traces until shutdown
     * and then posts them one HTTP request at a time. Cap what we keep so the process can finish.
     */
    private const MAX_QUEUED_EVENTS = 50;

    protected const REDACTED_PARAMS = ['twoFactorAuthCode', 'pass', 'passAgain', 'ticket', 'TOKEN'];
    protected const REDACTED_SESSION = ['', 'CV', '_CSRF'];

    protected int $state = self::STATE_DISABLED;

    protected bool $phpEnabled;
    protected bool $jsEnabled;
    protected bool $tracingEnabledPhp;
    protected bool $tracingEnabledJs;

    protected string $dsn;
    private bool $dsnIsInvalid = false;
    private bool $isInitialised = false;
    protected float $sampleRate;
    protected float $tracesSampleRate;

    protected array $stack = [];

    /** @var array<string, true> Distinct errors already accepted this process, keyed by a fingerprint */
    private array $reportedFingerprints = [];

    private int $suppressedCount = 0;

    /** True while a report is being built or sent, so a warning from that work cannot re-enter and loop */
    private bool $capturing = false;

    private ?closure $previousErrorHandler = null;
    private $currentTransaction = null;

    /**
     * Check if external error reporting for JavaScript is enabled.
     *
     * @return bool
     */
    public function isJSEnabled(): bool
    {
        return isset($this->dsn) && $this->jsEnabled;
    }

    /**
     * Check if performance tracing for PHP is enabled.
     *
     * @return bool
     */
    public function isTracingEnabledPhp(): bool
    {
        return isset($this->dsn) && $this->phpEnabled && $this->tracingEnabledPhp;
    }

    /**
     * Check if performance tracing for JavaScript is enabled.
     *
     * @return bool
     */
    public function isTracingEnabledJs(): bool
    {
        return isset($this->dsn) && $this->jsEnabled && $this->tracingEnabledJs;
    }

    /**
     * Report a PHP error without keeping the live function arguments in the trace.
     *
     * Scheduler and tracker sync warnings often fire inside loops that hold the whole
     * dataset in local variables. Those arguments stay reachable for as long as the
     * exception (and the queued Sentry event) do.
     */
    public function capturePhpError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if ($this->state === self::STATE_DISABLED || $this->capturing) {
            return;
        }

        $previous = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '1');
        try {
            $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        } finally {
            if ($previous !== false) {
                ini_set('zend.exception_ignore_args', $previous);
            }
        }

        $this->captureException($exception);
    }

    /**
     * Capture a thrown exception.
     *
     * Identical errors are counted instead of queued again. The queue itself is capped
     * so a long command cannot retain every distinct warning until process shutdown.
     *
     * @param \Throwable $exception The exception to capture
     * @param array $tags Optional associative array of tags to attach to this specific event only
     */
    public function captureException(\Throwable $exception, array $tags = [])
    {
        if ($this->state === self::STATE_DISABLED || $this->capturing) {
            return;
        }

        $fingerprint = $this->fingerprint($exception);
        if (isset($this->reportedFingerprints[$fingerprint])) {
            $this->suppressedCount++;
            return;
        }
        if (count($this->reportedFingerprints) >= self::MAX_QUEUED_EVENTS) {
            $this->suppressedCount++;
            return;
        }
        $this->reportedFingerprints[$fingerprint] = true;

        $this->capturing = true;
        try {
            if (empty($tags)) {
                // No event-specific tags, capture directly
                \Sentry\captureException($exception);
            } else {
                // Use withScope to set event-specific tags without polluting global scope
                \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($exception, $tags) {
                    foreach ($tags as $key => $value) {
                        $scope->setTag($key, (string)$value);
                    }
                    \Sentry\captureException($exception);
                });
            }
        } finally {
            $this->capturing = false;
        }
    }

    private function fingerprint(\Throwable $exception): string
    {
        $severity = $exception instanceof \ErrorException ? (string) $exception->getSeverity() : '';

        return hash(
            'md5',
            get_class($exception) . "\0" . $severity . "\0" . $exception->getFile() . "\0" . $exception->getLine() . "\0" . $exception->getMessage()
        );
    }

    /**
     * Start a new transaction for performance tracing
     *
     * @param string $name Transaction name (e.g., 'GET /tiki-index.php')
     * @param string $op Operation type (e.g., 'http.server', 'db.query', 'cache.get')
     * @return \Sentry\Tracing\Transaction|null
     */
    public function startTransaction(string $name, string $op = 'http.server')
    {
        if ($this->state === self::STATE_DISABLED) {
            return null;
        }

        $transactionContext = \Sentry\Tracing\TransactionContext::make()
            ->setName($name)
            ->setOp($op);

        $transaction = \Sentry\startTransaction($transactionContext);

        // Set as current span to maintain proper hierarchy
        \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

        return $transaction;
    }

    /**
     * Start a child span within a transaction
     *
     * @param string $op Operation type (e.g., 'db.query', 'http.client', 'cache.get')
     * @param string|null $description Optional description
     * @param array $data Optional data to attach to the span
     * @return \Sentry\Tracing\Span|null
     */
    public function startSpan(string $op, ?string $description = null, array $data = [])
    {
        if ($this->state === self::STATE_DISABLED) {
            return null;
        }

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        if (! $parent) {
            // No active transaction/span, cannot create child span
            return null;
        }

        $context = \Sentry\Tracing\SpanContext::make()
            ->setOp($op);

        if ($description) {
            $context->setDescription($description);
        }

        if (! empty($data)) {
            $context->setData($data);
        }

        $span = $parent->startChild($context);

        // Set as current span to maintain hierarchy
        \Sentry\SentrySdk::getCurrentHub()->setSpan($span);

        return $span;
    }

    /**
     * Finish a span and restore parent context
     *
     * @param \Sentry\Tracing\Span|null $span The span to finish
     * @return void
     */
    public function finishSpan($span): void
    {
        if (! $span) {
            return;
        }

        // Get parent before finishing
        $parent = $span->getParentSpanId() !== null ? $span : null;

        $span->finish();

        // Restore parent span context if available
        $hub = \Sentry\SentrySdk::getCurrentHub();
        if ($parent && $hub->getSpan() === $span) {
            // Restore to transaction or parent span
            $transaction = $this->currentTransaction;
            if ($transaction) {
                $hub->setSpan($transaction);
            }
        }
    }

    /**
     * Trace a callable with automatic span management (recommended approach)
     *
     * This is a convenience wrapper that automatically creates, manages, and finishes
     * a span around the provided callable, following Sentry best practices.
     *
     * @param callable $callback The function to trace
     * @param string $op Operation type (e.g., 'db.query', 'http.client')
     * @param string|null $description Optional description
     * @param array $data Optional data to attach to the span
     * @return mixed The return value of the callback
     *
     * @example
     * $result = $errorTracking->trace(function() {
     *     return performDatabaseQuery();
     * }, 'db.query', 'SELECT * FROM users');
     */
    public function trace(callable $callback, string $op, ?string $description = null, array $data = [])
    {
        if (! $this->isTracingEnabledPhp()) {
            // Tracing disabled, just execute callback
            return $callback();
        }

        $context = \Sentry\Tracing\SpanContext::make()
            ->setOp($op);

        if ($description) {
            $context->setDescription($description);
        }

        if (! empty($data)) {
            $context->setData($data);
        }

        // Use Sentry's trace function which handles everything automatically
        return \Sentry\trace($callback, $context);
    }


    /**
     * Capture thrown exception. Exceptions are always added to the exceptions stack.
     *
     * @param Event $event
     */
    protected function registerEvent(Event $event)
    {
        $this->stack[] = $event;
    }

    /**
     * ErrorTracking constructor.
     * Initializes internal variables and Sentry itself.
     *
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        global $prefs;
        $this->phpEnabled = ($prefs['error_tracking_enabled_php'] ?? 'n') === 'y';
        $this->jsEnabled = ($prefs['error_tracking_enabled_js'] ?? 'n') === 'y';
        $this->tracingEnabledPhp = ($prefs['error_tracking_tracing_enabled_php'] ?? 'n') === 'y';
        $this->tracingEnabledJs = ($prefs['error_tracking_tracing_enabled_js'] ?? 'n') === 'y';

        if (! self::LOCAL_DEBUG_MODE) {
            $this->dsn = $prefs['error_tracking_dsn'] ?? false;
        } else {
            //Sentry is picky about DSN format. This will work:
            $this->dsn = 'https://something@dummydsn.com/something';
        }


        $sampleRate = $prefs['error_tracking_sample_rate'] ?? 1;
        $this->sampleRate = is_numeric($sampleRate) ? $sampleRate : 1;

        $tracesSampleRate = $prefs['error_tracking_traces_sample_rate'] ?? 0.1;
        $this->tracesSampleRate = is_numeric($tracesSampleRate) ? $tracesSampleRate : 0.1;
    }

    public function init()
    {
        global $prefs;
        if ($this->isInitialised) {
            // The DI container keeps this instance for the life of a PHP worker. Start each request
            // with an empty queue instead of sending every new error immediately or throwing.
            $this->stack = [];
            $this->reportedFingerprints = [];
            $this->suppressedCount = 0;
            if ($this->state !== self::STATE_DISABLED) {
                $this->setState(self::STATE_HOLD);
            }
            return;
        }
        if (! self::LOCAL_DEBUG_MODE && (! isset($this->dsn) || ! $this->phpEnabled || $this->state !== self::STATE_DISABLED)) {
            return;
        }
        try {
            Sentry\init([
                'dsn'                     => $this->getDSN(),
                'http_proxy'              => ($prefs['use_proxy'] ?? 'n') === 'y' ? $this->getProxyURL() : null,
                'sample_rate'             => $this->getSampleRate(),
                'error_types'             => Errors::getErrorReportingLevel(),
                'attach_stacktrace'       => true,
                'traces_sampler' => function (\Sentry\Tracing\SamplingContext $context): float {
                    // If tracing is not enabled, don't sample any transactions
                    if (! $this->isTracingEnabledPhp()) {
                        return 0.0;
                    }

                    // Custom sampling logic based on transaction name and context
                    $transactionContext = $context->getTransactionContext();
                    $parentSampled = $context->getParentSampled();

                    // Inherit parent sampling decision if available
                    if ($parentSampled !== null) {
                        return $parentSampled ? 1.0 : 0.0;
                    }

                    $transactionName = $transactionContext->getName();

                    // Don't trace health checks or status endpoints
                    if (preg_match('/\/(health|status|ping)/', $transactionName)) {
                        return 0.0;
                    }

                    // Sample AJAX/API calls at a higher rate (tiki-ajax_services.php)
                    if (strpos($transactionName, 'tiki-ajax_services.php') !== false) {
                        return min($this->getTracesSampleRate() * 2.0, 1.0);
                    }

                    // Sample admin pages at a lower rate (they're usually slower)
                    if (strpos($transactionName, 'tiki-admin') !== false) {
                        return min($this->getTracesSampleRate() * 0.5, 1.0);
                    }

                    // Use the configured default sample rate
                    return $this->getTracesSampleRate();
                },
                // A shared curl handle keeps the GlitchTip connection open across reports. With a
                // flood of warnings that connection stops completing and the command never exits.
                'http_enable_curl_share_handle' => false,
                'integrations'            => static function (array $integrations): array {
                    // PHP errors are reported from Tiki's own handlers so repeated warnings can be
                    // dropped before Sentry builds a stack trace. Sentry's listener would capture
                    // every warning first, including the duplicate report from console.php.
                    $filtered = [];
                    foreach ($integrations as $integration) {
                        if ($integration instanceof ErrorListenerIntegration) {
                            continue;
                        }
                        $filtered[] = $integration;
                    }
                    return $filtered;
                },
                'before_send'             => function (Event $event, ?EventHint $hint): ?Event {
                    if (true && self::LOCAL_DEBUG_MODE) {
                        echo '<pre>';
                        // @phpstan-ignore disallowedFunctions.printR (local debug only, gated by LOCAL_DEBUG_MODE constant)
                        print_r("Incoming sentry event:<br/>");
                        //cho $event->getId();
                        echo $event->getLevel() . ': ' . $event->getMessage();
                        print($hint->exception->getMessage());
                        echo '<br/></pre>';
                    }

                    $this->boundEventFrameVars($event);

                    if ($this->state === self::STATE_PUSH) {
                        // only filter entries when pushing, since will not impact rendering time for pages
                        $eventExceptions = $event->getExceptions();
                        if ($eventExceptions) {
                            $frame = null;
                            foreach ($eventExceptions as &$exception) {
                                $stackTrace = $exception->getStacktrace();
                                if (empty($stackTrace)) {
                                    continue;
                                }
                                $frames = $stackTrace->getFrames();
                                if (empty($frames)) {
                                    continue;
                                }
                                foreach ($frames as &$frame) {
                                    $vars = $frame->getVars();
                                    $this->redactEntries($vars);
                                    $frame->setVars($vars);
                                }
                                $exception->setStacktrace(new \Sentry\Stacktrace($frames));
                            }
                            unset($exception, $frame);
                            $event->setExceptions($eventExceptions);
                        }

                        return $event;
                    }

                    if ($this->state === self::STATE_HOLD) {
                        if (empty($event->getUser())) {
                            // Set here because when we run the function from Sentry\configureScope user may not be set
                            global $user;
                            $event->setUser(Sentry\UserDataBag::createFromArray(['username' => $user ?? 'Anonymous']));
                        }
                        $this->registerEvent($event);
                    }

                    return null;
                },
                'before_send_transaction' => function (Event $transaction): ?Event {
                    if (false && self::LOCAL_DEBUG_MODE) {
                        echo '<pre>';
                        // @phpstan-ignore disallowedFunctions.printR (local debug only, block is always dead: false && LOCAL_DEBUG_MODE)
                        print_r("Incoming sentry transaction:<br/>");
                        // @phpstan-ignore disallowedFunctions.varDump (local debug only, block is always dead: false && LOCAL_DEBUG_MODE)
                        var_dump($transaction);
                        echo '</pre>';
                    }
                    return $transaction;
                },
            ]);

            $this->setState(self::STATE_HOLD);

            Sentry\configureScope(function (Sentry\State\Scope $scope) {
                // track REQUEST parameters
                $requestCopy = $_REQUEST;
                $this->redactEntries($requestCopy);
                $scope->setExtra('_REQUEST', $requestCopy);
            });
        } catch (Symfony\Component\OptionsResolver\Exception\InvalidOptionsException $e) {
            //Without this catch, if you enter an invalid DSN, you won't be able to access the tiki admin interface.
            $message = 'Tiki:  the options in error_tracking_dsn was refused by Sentry\init() with message: ' . $e->getMessage();
            //We can't echo anything to the HTML output yet, it will get stripped off if you try

            $this->dsnIsInvalid = true;
            //At least this will log to server log
            trigger_error('Tiki:  the options in error_tracking_dsn was refused by Sentry\init() with message: ' . $e->getMessage(), E_USER_WARNING);
        }
        $this->isInitialised = true;
    }

    /**
     * Push exceptions to third party service
     *
     * @return void
     */
    private function pushEvents()
    {
        if ($this->capturing) {
            return;
        }

        $events = $this->stack;
        $this->stack = [];
        $suppressed = $this->suppressedCount;
        $this->suppressedCount = 0;

        // Hold off nested reports while the HTTP posts run. A warning from the client
        // (or from closing the connection) would otherwise queue another send.
        $this->capturing = true;
        try {
            foreach ($events as $index => $event) {
                \Sentry\captureEvent($event);
                unset($events[$index], $event);
            }

            if ($suppressed > 0) {
                \Sentry\captureMessage(sprintf(
                    'Suppressed %d additional PHP errors in this process so error reporting could finish. %d distinct errors were queued.',
                    $suppressed,
                    count($this->reportedFingerprints)
                ));
            }
        } finally {
            $this->capturing = false;
            $this->setSampleRate($this->sampleRate);
        }
    }

    /**
     * Set current state of the Error Tracking.
     *
     * @param int $state
     */
    private function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Set the client sample_rate
     *
     * @param float $rate
     *
     * @return void
     */
    private function setSampleRate(float $rate): void
    {
        \Sentry\SentrySdk::getCurrentHub()
            ->getClient()
            ->getOptions()
            ->setSampleRate($rate);
    }

    /**
     * Get currently configured project Data Source Name
     *
     * @return string
     */
    public function getDSN(): string
    {
        return $this->dsn;
    }

    /**
     * Get currently configured sample rate
     *
     * @return float
     */
    public function getSampleRate(): float
    {
        return (float) $this->sampleRate;
    }

    /**
     * Get currently configured traces sample rate
     *
     * @return float
     */
    public function getTracesSampleRate(): float
    {
        return (float) $this->tracesSampleRate;
    }

    /**
     * Get the proxy connection url
     *
     * @return string
     */
    private function getProxyURL(): string
    {
        global $prefs;

        $proxy = '';

        if (! empty($prefs['proxy_user']) && ! empty($prefs['proxy_pass'])) {
            $proxy .= $prefs['proxy_user'] . ':' . $prefs['proxy_pass'] . '@';
        }

        $proxy .= $prefs['proxy_host'];

        if (isset($prefs['proxy_port'])) {
            $proxy .= ':' . $prefs['proxy_port'];
        }

        return $proxy;
    }

    /**
     * Start an automatic transaction for the current HTTP request
     *
     * @return void
     */
    public function startHttpTransaction(): void
    {
        if (! $this->isTracingEnabledPhp() || $this->currentTransaction !== null) {
            return;
        }

        // Build transaction name from request
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string for cleaner transaction names
        $path = parse_url($uri, PHP_URL_PATH) ?? $uri;

        $transactionName = "$method $path";

        $this->currentTransaction = $this->startTransaction($transactionName, 'http.server');

        if ($this->currentTransaction) {
            // Add request context
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($method, $uri) {
                $scope->setTag('http.method', $method);
                $scope->setTag('http.url', $uri);
            });
        }
    }

    /**
     * Finish the current transaction
     *
     * @return void
     */
    public function finishTransaction(): void
    {
        if ($this->currentTransaction) {
            $this->currentTransaction->finish();
            $this->currentTransaction = null;
        }
    }

    public function bindEvents(Tiki_Event_Manager $manager)
    {
        if ($this->state !== self::STATE_DISABLED) {
            $manager->bind(
                'tiki.process.shutdown',
                function () {
                    // Finish any open transaction before shutdown
                    $this->finishTransaction();

                    // Events were already sampled when prepared
                    // Setting to 1 will send all of them
                    $this->setSampleRate(1);
                    $this->setState(self::STATE_PUSH);
                    $this->pushEvents();
                }
            );
        }
    }

    /**
     * A callback for PHP set_error_handler()
     *
     * In practice, this is called directly by initlib::tiki_error_handling
     *
     * Set how Tiki will report Errors
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     *
     * @return bool Skip running any other error handler after this one.
     */
    public function handleError($errno, $errstr, $errfile, $errline): bool
    {
        $this->capturePhpError($errno, $errstr, $errfile, $errline);

        if ($this->previousErrorHandler) {
            return ($this->previousErrorHandler)($errno, $errstr, $errfile, $errline);
        }
        //If there was no previousErrorHandler, we do not want PHPs default handler to run.
        return true;
    }

    public function setPreviousErrorHandler(Closure $handler)
    {
        $this->previousErrorHandler = $handler;
    }

    /**
     * Drop frame variables that would retain a tracker payload or other large structure.
     */
    private function boundEventFrameVars(Event $event): void
    {
        foreach ($event->getExceptions() as $exception) {
            $stackTrace = $exception->getStacktrace();
            if ($stackTrace === null) {
                continue;
            }
            foreach ($stackTrace->getFrames() as $frame) {
                $vars = $frame->getVars();
                $bounded = $this->boundFrameVars($vars);
                if ($bounded !== $vars) {
                    $frame->setVars($bounded);
                }
            }
        }
    }

    private function boundFrameVars(array $vars): array
    {
        if ($this->valueIsLarge($vars)) {
            return ['_omitted' => 'Frame variables omitted to keep error reporting bounded.'];
        }

        return $vars;
    }

    /**
     * @param mixed $value
     */
    private function valueIsLarge($value): bool
    {
        if (is_string($value)) {
            return strlen($value) > 2048;
        }
        if (! is_array($value)) {
            return false;
        }
        if (count($value) > 20) {
            return true;
        }
        foreach ($value as $item) {
            if (is_string($item) && strlen($item) > 2048) {
                return true;
            }
            if (is_array($item) && count($item) > 20) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace entries that may have sensitive information with [Redacted]
     *
     * @param $arrayToProcess
     *
     * @return void
     */
    protected function redactEntries(&$arrayToProcess): void
    {
        static $redactedParametersLowercase = null;

        if ($redactedParametersLowercase === null) {
            $redactedSessionEntries = array_map( // prepend session name
                function ($item) {
                    return session_name() . $item;
                },
                self::REDACTED_SESSION
            );
            $redactedParametersLowercase = array_map(
                'strtolower',
                array_merge(self::REDACTED_PARAMS, $redactedSessionEntries)
            );
        }

        array_walk_recursive($arrayToProcess, function (&$item, $key) use ($redactedParametersLowercase) {
            if (in_array(strtolower($key), $redactedParametersLowercase)) {
                $item = '[Redacted]';
            }
        });
    }
}
