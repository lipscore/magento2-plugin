<?php

namespace Lipscore\RatingsReviews\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Accessor
{
    protected $scope;
    protected $scopeId;

    protected $scopeConfig;
    protected $resourceConfig;

    protected static $paths = array(
        'coupon'       => 'lipscore_coupons/coupons/',
        'brand'        => 'lipscore_general/product_brand/',
        'apiKey'       => 'lipscore_general/api_key/',
        'locale'       => 'lipscore_general/locale/',
        'emails'       => 'lipscore_general/emails/',
        'module'       => 'lipscore_general/module/',
        'coupons'      => 'lipscore_general/coupons/',
        'tracking'     => 'lipscore_plugin/',
        'storeGeneral' => 'general/',
    );

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        $websiteId,
        $storeId
    ){
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->scope   = $this->getScope($websiteId, $storeId);
        $this->scopeId = $this->getScopeId($websiteId, $storeId);
    }

    public function get($param, $type)
    {
        $key = $this->getKey($param, $type);
        return $this->getConfig($key);
    }

    public function set($param, $type, $value)
    {
        $key = $this->getKey($param, $type);
        return $this->setConfig($key, $value);
    }

    protected function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, $this->scope, $this->scopeId);
    }

    protected function setConfig($path, $value)
    {
        return $this->resourceConfig->saveConfig($path, $value, $this->scope, $this->scopeId);
    }

    protected function getKey($param, $type)
    {
        return static::$paths[$type] . $param;
    }

    protected function getScope($websiteId, $storeId)
    {
        if ($websiteId) {
            return ScopeInterface::SCOPE_WEBSITES;
        } elseif ($storeId) {
            return ScopeInterface::SCOPE_STORES;
        } else {
            return ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
    }

    protected function getScopeId($websiteId, $storeId)
    {
        return $websiteId ? $websiteId : $storeId;
    }
}
