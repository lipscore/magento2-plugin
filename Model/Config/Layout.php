<?php

namespace Lipscore\RatingsReviews\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class Layout
{
    const XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRICE_POSITION = 'lipscore_general/pdp/layout_above_price';
    const XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRODUCT_OPTIONS = 'lipscore_general/pdp/layout_above_options';
    const XML_PATH_LIPSCORE_PDP_LAYOUT_BELOW_PRODUCT_OPTIONS = 'lipscore_general/pdp/layout_below_options';

    protected $scopeConfig;

    protected $serializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    public function getPdpAbovePriceWidgets($store = null)
    {
        return $this->serializer->unserialize($this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRICE_POSITION,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    public function getPdpAboveOptionsWidgets($store = null)
    {
        return $this->serializer->unserialize($this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRODUCT_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    public function getPdpBelowOptionsWidgets($store = null)
    {
        return $this->serializer->unserialize($this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PDP_LAYOUT_BELOW_PRODUCT_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }
}
