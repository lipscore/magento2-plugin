<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class Logger
{
    protected static $ignoredWords = ['SQLSTATE'];

    protected $logger;

    protected $storeManager;

    protected $productMetadata;

    protected $transportBuilder;

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

    public function log($e)
    {
        try {
            $this->logger->critical($e);
        } catch (\Exception $e) {

        }
    }

    protected static function url($url)
    {
        return $url ?: 'N/A';
    }

    protected static function isIgnoredException($e)
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
