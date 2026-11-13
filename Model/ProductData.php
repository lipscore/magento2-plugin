<?php

namespace Lipscore\RatingsReviews\Model;

use Lipscore\RatingsReviews\Helper\Product;
use Lipscore\RatingsReviews\Helper\Widget;
use Magento\Framework\Registry;

class ProductData
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Product
     */
    protected $productHelper;

    /**
     * @var Widget
     */
    protected $widgetHelper;

    /**
     * Constructor.
     *
     * @param Registry $registry
     * @param Product $productHelper
     * @param Widget $widgetHelper
     */
    public function __construct(
        Registry $registry,
        Product $productHelper,
        Widget $widgetHelper
    ) {
        $this->registry = $registry;
        $this->productHelper = $productHelper;
        $this->widgetHelper = $widgetHelper;
    }

    /**
     * Get Lipscore attributes for the current product.
     *
     * @param \Magento\Catalog\Model\Product|null $product
     * @return array|null
     */
    public function getCurrentProductLsAttributes($product = null)
    {
        if (!$product) {
            $product = $this->getCurrentProduct();
        }

        if (!$product) {
            return;
        }

        $productData = $this->productHelper->getProductData($product, true);

        return $this->widgetHelper->getProductAttrs($productData);
    }

    /**
     * Get the current product from the registry.
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    protected function getCurrentProduct()
    {
        return $this->registry->registry('product');
    }
}
