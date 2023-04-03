<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;

class Logger
{
    protected static $ignoredWords = ['SQLSTATE'];

    protected $logger;
    protected $storeManager;
    protected $productMetadata;
    protected $transportBuilder;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
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
        return $url ? $url : 'N/A';
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
