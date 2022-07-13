<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Sentry\Event;
use Sentry\EventHint;

class ErrorTracking
{
    const STATE_DISABLED = 0;
    const STATE_HOLD = 1;
    const STATE_PUSH = 2;

    protected int $state = self::STATE_DISABLED;

    protected bool $phpEnabled;
    protected bool $jsEnabled;

    protected string $dsn;
    protected float $sampleRate;

    protected array $stack = [];

    protected ?closure $errorHandler;

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
    public function captureException(\Exception $exception)
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
        $this->dsn = $prefs['error_tracking_dsn'] ?? false;

        $sampleRate = $prefs['error_tracking_sample_rate'] ?? 1;
        $this->sampleRate = is_numeric($sampleRate) ? $sampleRate : 1;

        $this->init();
    }

    public function init()
    {
        global $prefs;

        if (! isset($this->dsn) || ! $this->phpEnabled || $this->state !== self::STATE_DISABLED) {
            return;
        }

        Sentry\init([
            'dsn'         => $this->getDSN(),
            'http_proxy'  => $prefs['use_proxy'] === 'y' ? $this->getProxyURL() : null,
            'sample_rate' => $this->getSampleRate(),
            'before_send' => function (Event $event, ?EventHint $hint): ?Event {

                if ($this->state === self::STATE_PUSH) {
                    return $event;
                }

                if ($this->state === self::STATE_HOLD) {
                    $this->registerEvent($event);
                }

                return null;
            },
        ]);

        $this->setState(self::STATE_HOLD);
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
     * Get currently configured dsn
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

    public function handleError($errno, $errstr, $errfile, $errline): bool
    {
        if ($this->errorHandler) {
            return false !== ($this->errorHandler)($errno, $errstr, $errfile, $errline);
        }

        return false;
    }

    public function setErrorHandler(Closure $handler) {
        $this->errorHandler = $handler;
    }

}
