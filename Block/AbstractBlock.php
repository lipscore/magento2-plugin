<?php

namespace Lipscore\RatingsReviews\Block;

use Lipscore\RatingsReviews\Helper\Product;
use Lipscore\RatingsReviews\Helper\Widget;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

abstract class AbstractBlock extends Template
{
    protected $coreRegistry;

    protected $productHelper;

    protected $widgetHelper;

    protected $logger;

    public function __construct(
        Context $context,
        Logger $logger,
        Registry $registry,
        Product $productHelper,
        Widget $widgetHelper,
        array $data = []
    ) {
        $this->coreRegistry  = $registry;
        $this->productHelper = $productHelper;
        $this->widgetHelper  = $widgetHelper;
        $this->logger        = $logger;

        parent::__construct($context, $data);
    }

    public function getLsProductAttrs()
    {
        $productAttrs = '';

        try {
            $productAttrs = $this->createProductAttrs();
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $productAttrs;
    }

    protected function createProductAttrs()
    {
        $product = $this->getCurrentProduct();

        if (!$product) {
            return;
        }

        $productData = $this->productHelper->getProductData($product);
        return $this->widgetHelper->getProductAttrs($productData);
    }

    protected function getCurrentProduct()
    {
        if ($this->getProduct()) {
            return $this->getProduct();
        } else {
            return $this->coreRegistry->registry('product');
        }
    }
}
