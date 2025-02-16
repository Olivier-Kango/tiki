<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Sentry\Event;
use Sentry\EventHint;
use Tiki\Errors;

/* This file handles reporting PHP errors to a remote service like glitchtip or sentry */

class ErrorTracking
{
    /** Set this to true when developing this tool.  It will ignore preferences and activate the code with a dummy DSN */
    private const LOCAL_DEBUG_MODE = false;

    private const STATE_DISABLED = 0;
    private const STATE_HOLD = 1;
    private const STATE_PUSH = 2;

    protected const REDACTED_PARAMS = ['twoFactorAuthCode', 'pass', 'passAgain', 'ticket', 'TOKEN'];
    protected const REDACTED_SESSION = ['', 'CV', '_CSRF'];

    protected int $state = self::STATE_DISABLED;

    protected bool $phpEnabled;
    protected bool $jsEnabled;

    protected string $dsn;
    private bool $dsnIsInvalid = false;
    private bool $isInitialised = false;
    protected float $sampleRate;

    protected array $stack = [];

    private ?closure $previousErrorHandler = null;

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
     * Capture thrown exception. Exceptions are always added to the exceptions stack.
     *
     * @param \Exception $exception
     */
    public function captureException(\Throwable $exception)
    {
        if ($this->state === self::STATE_DISABLED) {
            return;
        }
        \Sentry\captureException($exception);
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
        if (! self::LOCAL_DEBUG_MODE) {
            $this->dsn = $prefs['error_tracking_dsn'] ?? false;
        } else {
            //Sentry is picky about DSN format. This will work:
            $this->dsn = 'https://something@dummydsn.com/something';
        }


        $sampleRate = $prefs['error_tracking_sample_rate'] ?? 1;
        $this->sampleRate = is_numeric($sampleRate) ? $sampleRate : 1;
    }

    public function init()
    {
        global $prefs;
        if ($this->isInitialised) {
            throw new Error('Error tracking can only be initialised once, so we can control where that happens');
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
                'before_send'             => function (Event $event, ?EventHint $hint): ?Event {
                    if (true && self::LOCAL_DEBUG_MODE) {
                        echo '<pre>';
                        print_r("Incoming sentry event:<br/>");
                        //cho $event->getId();
                        echo $event->getLevel() . ': ' . $event->getMessage();
                        print($hint->exception->getMessage());
                        echo '<br/></pre>';
                    }

                    if ($this->state === self::STATE_PUSH) {
                        // only filter entries when pushing, since will not impact rendering time for pages
                        $eventExceptions = $event->getExceptions();
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
                        $event->setExceptions($eventExceptions);

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
                        print_r("Incoming sentry transaction:<br/>");
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
        foreach ($this->stack as $event) {
            \Sentry\captureEvent($event);
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

    public function bindEvents(Tiki_Event_Manager $manager)
    {
        if ($this->state !== self::STATE_DISABLED) {
            $manager->bind(
                'tiki.process.shutdown',
                function () {
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
