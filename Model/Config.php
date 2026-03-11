<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model;

use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const DEFAULT_FRESH_HOURS = 12;
    private const DEFAULT_STALE_HOURS = 72;
    private const DEFAULT_TTL_HOURS   = 24;

    const XML_PATH_LIPSCORE_API_KEY = 'lipscore_general/api_key/api_key';
    const XML_PATH_LIPSCORE_API_SECRET = 'lipscore_general/api_key/secret';
    const XML_PATH_WEBHOOK_ENABLED = 'lipscore_general/webhook/enabled';
    const XML_PATH_WEBHOOK_SECRET  = 'lipscore_general/webhook/hmac_secret';
    const XML_PATH_JSONLD_ENABLED  = 'lipscore_general/jsonld/enabled';
    const XML_PATH_FRESH_THRESHOLD = 'lipscore/cache/fresh_threshold_hours';
    const XML_PATH_STALE_THRESHOLD = 'lipscore/cache/stale_threshold_hours';
    const XML_PATH_CACHE_TTL       = 'lipscore/cache/ttl_hours';
    const XML_PATH_CACHE_FPC_CLEAR = 'lipscore/cache/refresh_fpc';
    const XML_PATH_LIPSCORE_LOG_ENABLED = 'lipscore/log/enabled';
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

    protected $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Manager $manager,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->manager = $manager;
        $this->encryptor = $encryptor;
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
        return $this->manager->isEnabled(Module::MODULE_NAME) && $this->isActive($store);
    }

    public function isLipscoreOutputEnabled()
    {
        return $this->manager->isOutputEnabled(Module::MODULE_NAME) && $this->isActive();
    }

    public function isFpcClearEnabled()
    {
        return (bool) $this->scopeConfig->isSetFlag(self::XML_PATH_CACHE_FPC_CLEAR);
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

    public function getApiKeyForScope(string $scope, mixed $scopeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_KEY,
            $scope,
            $scopeId
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

    public function getApiUrlForScope(string $scope, mixed $scopeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_URL,
            $scope,
            $scopeId
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

    public function getApiSecretForScope(string $scope, mixed $scopeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_API_SECRET,
            $scope,
            $scopeId
        );
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

    public function isWebhookEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_WEBHOOK_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store);
    }

    public function isWebhookEnabledForScope(
        string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        mixed $scopeId = null
    ): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_WEBHOOK_ENABLED,
            $scope,
            $scopeId
        );
    }

    public function isJsonLdEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_JSONLD_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getWebhookHmacSecret(): string
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_WEBHOOK_SECRET);
        if ($value) {
            return (string) $this->encryptor->decrypt($value);
        }

        return $value;
    }

    public function getFreshThreshold($store = null): int
    {
        $hours = (int) $this->scopeConfig->getValue(
            self::XML_PATH_FRESH_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return ($hours > 0 ? $hours : self::DEFAULT_FRESH_HOURS) * 3600;
    }

    public function getStaleThreshold($store = null): int
    {
        $hours = (int) $this->scopeConfig->getValue(
            self::XML_PATH_STALE_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return ($hours > 0 ? $hours : self::DEFAULT_STALE_HOURS) * 3600;
    }

    public function getCacheTtl($store = null): int
    {
        $hours = (int) $this->scopeConfig->getValue(
            self::XML_PATH_CACHE_TTL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return ($hours > 0 ? $hours : self::DEFAULT_TTL_HOURS) * 3600;
    }

    public function isLogEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LIPSCORE_LOG_ENABLED
        );
    }
}
