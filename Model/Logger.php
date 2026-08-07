<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class Logger
{
    /**
     * @var array
     */
    protected static $ignoredWords = ['SQLSTATE'];

    /**
     * @var \Lipscore\RatingsReviews\Model\Logger\Logger
     */
    protected $logger;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Constructor.
     *
     * @param \Lipscore\RatingsReviews\Model\Logger\Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMetadata
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger\Logger $logger,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        TransportBuilder $transportBuilder
    ) {
        $this->logger           = $logger;
        $this->storeManager     = $storeManager;
        $this->productMetadata  = $productMetadata;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Log an exception.
     *
     * @param \Exception $e
     * @return void
     */
    public function log($e)
    {
        try {
            $this->logger->critical($e);
        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- intentionally suppressed to avoid a logging-failure loop
        } catch (\Exception $e) {
            // Logging itself failed; nothing further we can do here.
        }
    }

    /**
     * Return the given URL or a fallback placeholder.
     *
     * @param string $url
     * @return string
     */
    protected function url($url)
    {
        return $url ?: 'N/A';
    }

    /**
     * Check whether the exception message contains an ignored word.
     *
     * @param \Exception|string $e
     * @return bool
     */
    protected function isIgnoredException($e)
    {
        $found = false;
        foreach (static::$ignoredWords as $key => $word) {
            $message = is_string($e) ? $e : $e->getMessage();
            $found = strpos($message, $word) !== false;
            if ($found) {
                break;
            }
        }
        return $found;
    }
}
