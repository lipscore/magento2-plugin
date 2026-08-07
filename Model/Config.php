<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_LIPSCORE_API_KEY = 'lipscore_general/api_key/api_key';
    public const XML_PATH_LIPSCORE_API_SECRET = 'lipscore_general/api_key/secret';
    public const XML_PATH_LIPSCORE_ASSETS_URL = 'lipscore_general/api_key/assets_url';
    public const XML_PATH_LIPSCORE_API_URL = 'lipscore_general/api_key/api_url';
    public const XML_PATH_LIPSCORE_PRODUCT_ATTR_ID = 'lipscore_general/product_attributes/id';
    public const XML_PATH_LIPSCORE_PRODUCT_ATTR_BRAND = 'lipscore_general/product_attributes/brand';
    public const XML_PATH_LIPSCORE_PRODUCT_ATTR_GTIN = 'lipscore_general/product_attributes/gtin';
    public const XML_PATH_LIPSCORE_PRODUCT_ATTR_MPN = 'lipscore_general/product_attributes/mpn';
    public const XML_PATH_LIPSCORE_SHOW_CHILD_DATA_IN_PARENT
        = 'lipscore_general/product_attributes/show_child_data_in_parent';
    public const XML_PATH_LIPSCORE_LOCALE_LOCALE = 'lipscore_general/locale/locale';
    public const XML_PATH_LIPSCORE_EMAILS_TEXT = 'lipscore_general/emails/emails_text';
    public const XML_PATH_LIPSCORE_EMAILS_ORDER_STATUS = 'lipscore_general/emails/order_status';
    public const XML_PATH_LIPSCORE_COUPONS_TEXT = 'lipscore_general/coupons/coupons_text';
    public const XML_PATH_LIPSCORE_COUPONS_PRICE_RULE_ID = 'lipscore_general/coupons/price_rule_id';
    public const XML_PATH_LIPSCORE_APPEARANCE_RATINGS = 'lipscore_general/appearance/ratings';
    public const XML_PATH_LIPSCORE_APPEARANCE_REVIEW = 'lipscore_general/appearance/review';
    public const XML_PATH_LIPSCORE_APPEARANCE_QA = 'lipscore_general/appearance/qa';
    public const XML_PATH_LIPSCORE_MODULE_ACTIVE = 'lipscore_general/module/active';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Config constructor.
     *
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Manager $manager
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Manager $manager
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->manager = $manager;
    }

    /**
     * Get the parent source ID used when reporting orders to Lipscore.
     *
     * @return string
     */
    public function getParentSourceId()
    {
        return Module::PARENT_SOURCE_ID;
    }

    /**
     * Get the parent source name used when reporting orders to Lipscore.
     *
     * @return string
     */
    public function getParentSourceName()
    {
        return Module::PARENT_SOURCE_NAME;
    }

    /**
     * Check whether a valid (non-demo) API key is configured.
     *
     * @return bool
     */
    public function isValidApiKey()
    {
        return $this->getApiKey() && !$this->isDemoKey();
    }

    /**
     * Check whether the Lipscore module is enabled for the given store.
     *
     * @param string|int|null $store Store code or ID
     * @return bool
     */
    public function isLipscoreModuleEnabled($store = null)
    {
        try {
            return $this->manager->isEnabled(Module::MODULE_NAME) && $this->isActive($store);
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    /**
     * Check whether the Lipscore module output is enabled.
     *
     * @return bool
     */
    public function isLipscoreOutputEnabled()
    {
        try {
            return $this->manager->isOutputEnabled(Module::MODULE_NAME) && $this->isActive();
        } catch (\Exception $e) {
            $this->logger->log($e);
            return false;
        }
    }

    /**
     * Get the default locale configured for the given store.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getStoreLocale($store = null)
    {
        return $this->scopeConfig->getValue(
            Data::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the Lipscore API key.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getApiKey($store = null)
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * Get the Lipscore assets URL.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getAssetsUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_ASSETS_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the Lipscore API URL.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getApiUrl($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the Lipscore API secret key.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getApiSecret($store = null)
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_SECRET,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * Check whether child product data can be shown on the parent product.
     *
     * @param string|int|null $store Store code or ID
     * @return bool
     */
    public function canShowChildDataInParent($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_SHOW_CHILD_DATA_IN_PARENT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the product attribute code used for the Lipscore product ID.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getProductAttributeId($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the product attribute code used for the GTIN.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getProductAttributeGtin($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_GTIN,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the product attribute code used for the MPN.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getProductAttributeMpn($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_MPN,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the product attribute code used for the brand.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getProductAttributeBrand($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PRODUCT_ATTR_BRAND,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the Lipscore locale configured for the given store.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getLocale($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_LOCALE_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the Lipscore emails text.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getEmailsText($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_EMAILS_TEXT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the order status that triggers Lipscore review request emails.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getEmailsOrderStatus($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_EMAILS_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the Lipscore coupons text.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getCouponsText($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_COUPONS_TEXT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the cart price rule ID used for Lipscore coupons.
     *
     * @param string|int|null $store Store code or ID
     * @return string
     */
    public function getCouponsPriceRuleId($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_COUPONS_PRICE_RULE_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether the Lipscore Q&A widget can be shown.
     *
     * @param string|int|null $store Store code or ID
     * @return bool
     */
    public function canShowQa($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_APPEARANCE_QA,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether the Lipscore review tab can be shown.
     *
     * @param string|int|null $store Store code or ID
     * @return bool
     */
    public function canShowReviewTab($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_APPEARANCE_REVIEW,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether the Lipscore ratings widget can be shown.
     *
     * @param string|int|null $store Store code or ID
     * @return bool
     */
    public function canShowRatings($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_APPEARANCE_RATINGS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check whether the Lipscore module is active for the given store.
     *
     * @param string|int|null $store Store code or ID
     * @return bool
     */
    public function isActive($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_MODULE_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
