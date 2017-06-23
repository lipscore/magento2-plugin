<?php

namespace Lipscore\RatingsReviews\Block;

abstract class AbstractWidget extends \Magento\Framework\View\Element\Template
{
    protected $productAttrs;

    protected $coreRegistry;
    protected $productHelper;
    protected $widgetHelper;
    protected $logger;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Magento\Framework\Registry $registry,
        \Lipscore\RatingsReviews\Helper\Product $productHelper,
        \Lipscore\RatingsReviews\Helper\Widget $widgetHelper,
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
