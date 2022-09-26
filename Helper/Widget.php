<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;

class Widget extends AbstractHelper
{
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

    protected function _getProductAttrs($productData)
    {   
        $attrs = [
            'data-ls-product-name'   => $productData['name'],
            'data-ls-brand'          => $productData['brand'],
            'data-ls-sku'            => implode(';', $productData['sku_values']),
            'data-ls-product-id'     => $productData['internal_id'],
            'data-ls-image-url'      => $productData['image_url'],
            'data-ls-price'          => $productData['price'],
            'data-ls-price-currency' => $productData['currency'],
            'data-ls-category'       => $productData['category'],
            'data-ls-description'    => $productData['description'],
            'data-ls-availability'   => $productData['availability'],
            'data-ls-gtin'           => implode(';', $productData['gtin'])
        ];
        return $this->toString($attrs);
    }

    protected function toString($attrs)
    {
        $strAttrs = [];
        foreach ($attrs as $attr => $value) {
            $value = htmlspecialchars($value);
            $strAttrs[] = "$attr=\"$value\"";
        }
        return implode(' ', $strAttrs);
    }
}
