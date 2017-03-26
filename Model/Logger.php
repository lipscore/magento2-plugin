<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;

class Logger
{
    protected static $ignoredWords = ['SQLSTATE'];
    protected static $errorEmail   = 'server_error@lipscore.com';

    protected $logger;
    protected $storeManager;
    protected $transportBuilder;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ){
        $this->logger           = $logger;
        $this->storeManager     = $storeManager;
        $this->productMetadata  = $productMetadata;
        $this->transportBuilder = $transportBuilder;
    }

    public function log($e)
    {
        try {
            $this->logger->critical($e);

            if (static::isIgnoredException($e)) {
                return;
            }

            $this->sendEmail($e);
        } catch (\Exception $e) {}
    }

    protected static function url($url)
    {
        return $url ? $url : 'N/A';
    }

    protected function sendEmail($e)
    {
        $store = null;
        $storeInfo = $url = $to = '';

        try {
            $store = $this->storeManager->getStore();
        } catch (\Exception $e) {}

        if ($store) {
            $storeUrl  = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
            $storeInfo = $store->getFrontendName() . ', ' . static::url($storeUrl);
            $url       = $store->getCurrentUrl();
        } else {
            $storeInfo = 'N/A';
        }

        if (!$url && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            $url  = isset($_SERVER['HTTPS']) ? 'https' : 'http';
            $url .= '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        }

        $email   = static::$errorEmail;
        $errMsg  = is_string($e) ? $e : $e->getMessage();
        $trace   = is_string($e) ? 'N/A' : $e->getTraceAsString();
        $link    = static::url($url);
        $version = $this->productMetadata->getVersion();
        $sbj     = "Magento extension error: $errMsg";
        $msg     = "STORE: $storeInfo, $version\n\nERROR MESSAGE: $errMsg\n\nURL: $link\n\nSTACK TRACE: $trace";

        $transport = $this->transportBuilder
            ->setTemplateIdentifier('lipscore_ratingsreviews_email_error')
            ->setTemplateOptions([
                'area' => 'adminhtml',
                'store' => Store::DEFAULT_STORE_ID
            ])->setTemplateVars([
                'message' => nl2br($msg),
                'subject' => $sbj
            ])->setFrom(['email' => $email, 'name' => 'Lipscore exception logger'])
            ->addTo($email)
            ->getTransport();

        $transport->sendMessage();
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
