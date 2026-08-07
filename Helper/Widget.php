<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Framework\Escaper;
use Magento\Store\Model\StoreManagerInterface;

class Widget extends AbstractHelper
{
    public const WIDGET_SEPARATOR = ';';

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * Initialize dependencies.
     *
     * @param Logger $logger
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param Escaper $escaper
     */
    public function __construct(
        Logger $logger,
        Config $config,
        StoreManagerInterface $storeManager,
        Escaper $escaper
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->escaper = $escaper;
    }

    /**
     * Get the widget data attributes string for the given product data.
     *
     * @param array $productData
     * @return string
     */
    public function getProductAttrs($productData)
    {
        $attrs = '';
        try {
            $attrs = $this->_getProductAttrs($productData);
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $attrs;
    }

    /**
     * Build the widget data attributes string for the given product data.
     *
     * @param array $productData
     * @return string
     */
    protected function _getProductAttrs($productData)
    {
        $attrs = [
            'data-ls-product-name'   => $productData['name'],
            'data-ls-product-url'    => $productData['url'],
            'data-ls-brand'          => $productData['brand'],
            'data-ls-sku'            => implode(self::WIDGET_SEPARATOR, $productData['sku_values']),
            'data-ls-product-id'     => $productData['internal_id'],
            'data-ls-image-url'      => $productData['image_url'],
            'data-ls-price'          => $productData['price'],
            'data-ls-price-currency' => $productData['currency'],
            'data-ls-category'       => $productData['category'],
            'data-ls-description'    => $productData['description'],
            'data-ls-availability'   => $productData['availability'],
            'data-ls-gtin'           => implode(self::WIDGET_SEPARATOR, $productData['gtin']),
            'data-ls-mpn'            => $productData['mpn'],
        ];

        return $this->toString($attrs);
    }

    /**
     * Convert an array of attributes into an escaped HTML attribute string.
     *
     * @param array $attrs
     * @return string
     */
    protected function toString($attrs)
    {
        $strAttrs = [];
        foreach ($attrs as $attr => $value) {
            $value = isset($value) ? $value : '';
            $value = $this->escaper->escapeHtml($value);
            $strAttrs[] = "$attr=\"$value\"";
        }

        return implode(' ', $strAttrs);
    }
}
