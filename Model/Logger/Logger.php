<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\Logger;

use DateTimeZone;
use Lipscore\RatingsReviews\Model\Config;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    protected $config;

    public function __construct(
        string $name,
        array $handlers = [],
        array $processors = [],
        ?Config $config = null,
        ?DateTimeZone $timezone = null
    ) {
        $this->config = $config;
        parent::__construct($name, $handlers, $processors, $timezone);
    }

    public function debug($message, array $context = []): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        parent::debug($message, $context);
    }

    public function info($message, array $context = []): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        parent::info($message, $context);
    }

    public function warning($message, array $context = []): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        parent::warning($message, $context);
    }

    public function error($message, array $context = []): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        parent::error($message, $context);
    }

    private function isLoggingEnabled(): bool
    {
        return $this->config->isLogEnabled();
    }
}
