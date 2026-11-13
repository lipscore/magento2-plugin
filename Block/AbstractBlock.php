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
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ProductData
     */
    protected $productData;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Logger $logger
     * @param Registry $registry
     * @param ProductData $productData
     * @param Config $config
     * @param array $data
     */
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

    /**
     * Get Lipscore attributes for the current product.
     *
     * @return string
     */
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
