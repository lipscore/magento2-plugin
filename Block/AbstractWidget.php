<?php

namespace Lipscore\RatingsReviews\Block;

use Lipscore\RatingsReviews\Helper\Product;
use Lipscore\RatingsReviews\Helper\Widget;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ResourceConnection;

abstract class AbstractWidget extends Template
{
    const XML_PATH_MERGEREVIEWS_IS_ENABLED = 'mergereviews/general/is_enabled';
    protected $productAttrs;
    /**
     * @var Registry
     */
    protected $coreRegistry;
    /**
     * @var Product
     */
    protected $productHelper;
    /**
     * @var Widget
     */
    protected $widgetHelper;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param Registry $registry
     * @param Product $productHelper
     * @param Widget $widgetHelper
     * @param ResourceConnection $resourceConnection
     * @param ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Logger $logger,
        Registry $registry,
        Product $productHelper,
        Widget $widgetHelper,
        ResourceConnection $resourceConnection,
        ProductFactory $productFactory,
        array $data = []
    ) {
        $this->coreRegistry  = $registry;
        $this->productHelper = $productHelper;
        $this->widgetHelper  = $widgetHelper;
        $this->logger        = $logger;

        parent::__construct($context, $data);
        $this->resourceConnection = $resourceConnection;
        $this->productFactory = $productFactory;
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

    /**
     * @return \Magento\Catalog\Model\Product|mixed|null
     */
    protected function getCurrentProduct()
    {
        if(!$this->isEnabled()) {
            if ($this->getProduct()) {
                return $this->getProduct();
            } else {
                return $this->coreRegistry->registry('product');
            }
        }
        $product = $this->getProduct() ? $this->getProduct(): $this->coreRegistry->registry('product');
        $parentIds = $this->getParentIds($product->getId());
        if (!empty($parentIds)) {
            $parentId = $parentIds[0];
            $product = $this->productFactory->create()->load($parentId);
        }
        return $product;
    }

    /**
     * @param $childId
     * @return mixed
     */
    private function getParentIds($childId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('catalog_product_relation', 'parent_id')
            ->where('child_id = ?', (int)$childId);

        return $connection->fetchCol($select);
    }
    /**
     * @return bool
     */
    private function isEnabled()
    {
        $storeScope = ScopeInterface::SCOPE_STORE;

        return (bool)$this->_scopeConfig->getValue(self::XML_PATH_MERGEREVIEWS_IS_ENABLED, $storeScope);
    }
}
