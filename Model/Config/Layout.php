<?php

namespace Lipscore\RatingsReviews\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class Layout
{
    public const XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRICE_POSITION = 'lipscore_general/pdp/layout_above_price';
    public const XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRODUCT_OPTIONS = 'lipscore_general/pdp/layout_above_options';
    public const XML_PATH_LIPSCORE_PDP_LAYOUT_BELOW_PRODUCT_OPTIONS = 'lipscore_general/pdp/layout_below_options';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Layout constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    /**
     * Get the widgets configured to display above the price on the product page.
     *
     * @param int|string|null $store
     * @return array
     */
    public function getPdpAbovePriceWidgets($store = null)
    {
        return $this->serializer->unserialize($this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRICE_POSITION,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * Get the widgets configured to display above the product options on the product page.
     *
     * @param int|string|null $store
     * @return array
     */
    public function getPdpAboveOptionsWidgets($store = null)
    {
        return $this->serializer->unserialize($this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PDP_LAYOUT_ABOVE_PRODUCT_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * Get the widgets configured to display below the product options on the product page.
     *
     * @param int|string|null $store
     * @return array
     */
    public function getPdpBelowOptionsWidgets($store = null)
    {
        return $this->serializer->unserialize($this->scopeConfig->getValue(
            self::XML_PATH_LIPSCORE_PDP_LAYOUT_BELOW_PRODUCT_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }
}
