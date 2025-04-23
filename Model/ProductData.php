<?php

namespace Lipscore\RatingsReviews\Model;

use Lipscore\RatingsReviews\Helper\Product;
use Lipscore\RatingsReviews\Helper\Widget;
use Magento\Framework\Registry;

class ProductData
{
    protected $registry;

    protected $productHelper;

    protected $widgetHelper;

    public function __construct(
        Registry $registry,
        Product $productHelper,
        Widget $widgetHelper
    ) {
        $this->registry = $registry;
        $this->productHelper = $productHelper;
        $this->widgetHelper = $widgetHelper;
    }

    public function getCurrentProductLsAttributes()
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return;
        }

        $productData = $this->productHelper->getProductData($product, true);

        return $this->widgetHelper->getProductAttrs($productData);
    }

    protected function getCurrentProduct()
    {
        return $this->registry->registry('product');
    }
}
