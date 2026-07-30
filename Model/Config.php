<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH_LIPSCORE_API_KEY = 'lipscore_general/api_key/api_key';
    const XML_PATH_LIPSCORE_API_SECRET = 'lipscore_general/api_key/secret';
    const XML_PATH_LIPSCORE_ASSETS_URL = 'lipscore_general/api_key/assets_url';
    const XML_PATH_LIPSCORE_API_URL = 'lipscore_general/api_key/api_url';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_ID = 'lipscore_general/product_attributes/id';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_BRAND = 'lipscore_general/product_attributes/brand';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_GTIN = 'lipscore_general/product_attributes/gtin';
    const XML_PATH_LIPSCORE_PRODUCT_ATTR_MPN = 'lipscore_general/product_attributes/mpn';
    const XML_PATH_LIPSCORE_SHOW_CHILD_DATA_IN_PARENT = 'lipscore_general/product_attributes/show_child_data_in_parent';
    const XML_PATH_LIPSCORE_LOCALE_LOCALE = 'lipscore_general/locale/locale';
    const XML_PATH_LIPSCORE_EMAILS_TEXT = 'lipscore_general/emails/emails_text';
    const XML_PATH_LIPSCORE_EMAILS_ORDER_STATUS = 'lipscore_general/emails/order_status';
    const XML_PATH_LIPSCORE_COUPONS_TEXT = 'lipscore_general/coupons/coupons_text';
    const XML_PATH_LIPSCORE_COUPONS_PRICE_RULE_ID = 'lipscore_general/coupons/price_rule_id';
    const XML_PATH_LIPSCORE_APPEARANCE_RATINGS = 'lipscore_general/appearance/ratings';
    const XML_PATH_LIPSCORE_APPEARANCE_REVIEW = 'lipscore_general/appearance/review';
    const XML_PATH_LIPSCORE_APPEARANCE_QA = 'lipscore_general/appearance/qa';
    const XML_PATH_LIPSCORE_MODULE_ACTIVE = 'lipscore_general/module/active';

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
        return Module::PARENT_SOURCE_ID;
    }

    public function getParentSourceName()
    {
        return Module::PARENT_SOURCE_NAME;
    }


    public function isValidApiKey()
    {
        return $this->getApiKey() && !$this->isDemoKey();
    }

    public function isLipscoreModuleEnabled($store = null)
    {
        try {
            return $this->manager->isEnabled(Module::MODULE_NAME) && $this->isActive($store);
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    public function isLipscoreOutputEnabled()
    {
        try {
            return $this->manager->isOutputEnabled(Module::MODULE_NAME) && $this->isActive();
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
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
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
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_SECRET,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    public function canShowChildDataInParent($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_SHOW_CHILD_DATA_IN_PARENT,
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
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_APPEARANCE_QA,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function canShowReviewTab($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_APPEARANCE_REVIEW,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function canShowRatings($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_APPEARANCE_RATINGS,
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
