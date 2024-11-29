<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Module\Manager;
use Magento\Directory\Helper\Data;

class Config
{
    const MODULE_NAME = 'Lipscore_RatingsReviews';
    const XML_PATH_LIPSCORE_API_KEY = 'lipscore_general/api_key/api_key';
    const XML_PATH_LIPSCORE_API_SECRET = 'lipscore_general/api_key/secret';
    const XML_PATH_LIPSCORE_ASSETS_URL = 'lipscore_general/api_key/assets_url';
    const XML_PATH_LIPSCORE_API_URL = 'lipscore_general/api_key/api_url';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_ID = 'lipscore_general/product_attributes/id';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_BRAND = 'lipscore_general/product_attributes/brand';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_GTIN = 'lipscore_general/product_attributes/gtin';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_MPN = 'lipscore_general/product_attributes/mpn';
    const XML_PATH_LIPSCORE_LOCALE_LOCALE = 'lipscore_general/locale/locale';
    const XML_PATH_LIPSCORE_EMAILS_TEXT = 'lipscore_general/emails/emails_text';
    const XML_PATH_LIPSCORE_EMAILS_ORDER_STATUS = 'lipscore_general/emails/order_status';
    const XML_PATH_LIPSCORE_COUPONS_TEXT = 'lipscore_general/coupons/coupons_text';
    const XML_PATH_LIPSCORE_COUPONS_PRICE_RULE_ID = 'lipscore_general/coupons/price_rule_id';
    const XML_PATH_LIPSCORE_APPEARANCE_QA = 'lipscore_general/appearance/qa';
    const XML_PATH_LIPSCORE_MODULE_ACTIVE = 'lipscore_general/module/active';

    const PARENT_SOURCE_ID = 'magento2';
    const PARENT_SOURCE_NAME = 'Magento 2';

    protected $scopeConfig;

    protected $manager;

    protected $logger;

    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Manager $manager
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->manager = $manager;
    }

    public function getParentSourceId()
    {
        return self::PARENT_SOURCE_ID;
    }

    public function getParentSourceName()
    {
        return self::PARENT_SOURCE_NAME;
    }

    public function isDemoKey()
    {
        $currentKey = $this->getApiKey();
        $demokey    = $this->demoApiKey();
        return $currentKey == $demokey;
    }

    public function isValidApiKey()
    {
        return $this->getApiKey() && !$this->isDemoKey();
    }

    public function isLipscoreModuleEnabled()
    {
        try {
            return $this->manager->isEnabled(self::MODULE_NAME) && $this->isActive();
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    public function isLipscoreOutputEnabled()
    {
        try {
            return $this->manager->isOutputEnabled(self::MODULE_NAME) && $this->isActive();
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    public function getStoreLocale($store = null)
    {
        return $this->scopeConfig->getValue(
            Data::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getApiKey($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getAssetsUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_ASSETS_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getApiUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getApiSecret($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_SECRET,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getProductAttributeId($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getProductAttributeGtin($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_GTIN,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getProductAttributeMpn($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_MPN,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getProductAttributeBrand($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_BRAND,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getLocale($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_LOCALE_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getEmailsText($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_EMAILS_TEXT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getEmailsOrderStatus($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_EMAILS_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getCouponsText($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_COUPONS_TEXT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getCouponsPriceRuleId($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_COUPONS_PRICE_RULE_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function canShowQa($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_APPEARANCE_QA,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isActive($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_MODULE_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
