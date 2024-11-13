<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

abstract class AbstractObserver implements ObserverInterface
{
    protected static $logFile = 'observer';

    protected static $logEnabled = false;

    protected $config;

    protected $logger;

    public function __construct(
        Config $config,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        try {
            $this->log($this->defaultLogMessage());
            if ($this->methodAvailable()) {
                return $this->_execute($observer);
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
    }

    abstract protected function methodAvailable();

    protected function defaultLogMessage()
    {
        return get_class($this);
    }

    protected function log($message)
    {
        if (!getenv('LIPSCORE_LOG_OBSERVER') && !static::$logEnabled) {
            return;
        }

        $filePath = BP . '/var/log/' . static::$logFile . '.log';
        $time     = date('d-m-Y H:i:s O');
        $message  = "$time " . print_r($message, true) . "\n";
        file_put_contents($filePath, $message, FILE_APPEND);
    }
}
