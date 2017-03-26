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
        } catch (Exception $e) {
            #Lipscore_RatingsReviews_Logger::logException($e);
        }
        return $attrs;
    }

    protected function _getProductAttrs($productData)
    {
        $attrs = array(
            'ls-product-name'   => $productData['name'],
            'ls-brand'          => $productData['brand'],
            'ls-sku'            => implode(';', $productData['sku_values']),
            'ls-product-id'     => $productData['internal_id'],
            'ls-image-url'      => $productData['image_url'],
            'ls-price'          => $productData['price'],
            'ls-price-currency' => $productData['currency'],
            'ls-category'       => $productData['category'],
            'ls-description'    => $productData['description'],
            'ls-availability'   => $productData['availability']

        );
        return $this->_toString($attrs);
    }

    protected function _toString($attrs)
    {
        $strAttrs = array();
        foreach ($attrs as $attr => $value) {
            $value = htmlspecialchars($value);
            $strAttrs[] = "$attr=\"$value\"";
        }
        return implode($strAttrs, ' ');
    }
}
