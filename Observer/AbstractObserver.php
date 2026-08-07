<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

abstract class AbstractObserver implements ObserverInterface
{
    /**
     * @var string
     */
    protected static $logFile = 'observer';

    /**
     * @var bool
     */
    protected static $logEnabled = false;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Initialize observer dependencies.
     *
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Execute the observer.
     *
     * @param Observer $observer
     * @return void
     */
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

    /**
     * Check whether the observer method is available to run.
     *
     * @return bool
     */
    abstract protected function methodAvailable();

    /**
     * Check whether the Lipscore extension is enabled and configured.
     *
     * @param string|int|null $store Store code or ID
     * @return bool
     */
    protected function checkIfEnabled($store = null)
    {
        $enabled = true;
        if (!$this->config->isLipscoreModuleEnabled($store)) {
            $this->log('extension is disabled');
            $enabled = false;
        }

        if (!$this->config->getApiKey($store)) {
            $this->log('API key is invalid');
            $enabled = false;
        }

        return $enabled;
    }

    /**
     * Check whether the given block renders empty content.
     *
     * @param \Magento\Framework\View\Layout $layout
     * @param string $blockName
     * @return bool
     */
    protected function checkIfBlockContentIsEmpty($layout, $blockName)
    {
        return !$layout->getBlock($blockName) || !$layout->getBlock($blockName)->toHtml();
    }

    /**
     * Get the default log message.
     *
     * @return string
     */
    protected function defaultLogMessage()
    {
        return get_class($this);
    }

    /**
     * Write a message to the observer log file.
     *
     * @param mixed $message
     * @return void
     */
    protected function log($message)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        if (!getenv('LIPSCORE_LOG_OBSERVER') && !static::$logEnabled) {
            return;
        }

        $filePath = BP . '/var/log/' . static::$logFile . '.log';
        $time     = date('d-m-Y H:i:s O');
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $message  = "$time " . print_r($message, true) . "\n";
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        file_put_contents($filePath, $message, FILE_APPEND);
    }
}
