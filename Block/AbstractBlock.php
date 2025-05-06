<?php

namespace Lipscore\RatingsReviews\Block;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\ProductData;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

abstract class AbstractBlock extends Template
{
    protected $coreRegistry;

    protected $productData;

    protected $logger;

    protected $config;

    public function __construct(
        Context $context,
        Logger $logger,
        Registry $registry,
        ProductData $productData,
        Config $config,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->productData = $productData;
        $this->logger = $logger;
        $this->config = $config;

        parent::__construct($context, $data);
    }

    public function getLsProductAttrs()
    {
        $productAttrs = '';

        try {
            $productAttrs = $this->productData->getCurrentProductLsAttributes($this->getProduct());
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $productAttrs;
    }
}
