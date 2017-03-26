<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Pricing\Price;

class Product extends AbstractHelper
{
    protected $productRepository;
    protected $imageHelper;
    protected $registry;
    protected $catalogCategory;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Model\Config\AbstractConfig $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Lipscore\RatingsReviews\Helper\Image $imageHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Category $catalogCategory
    ){
        parent::__construct($logger, $config, $storeManager);

        $this->productRepository = $productRepository;
        $this->imageHelper       = $imageHelper;
        $this->registry          = $registry;
        $this->catalogCategory   = $catalogCategory;
    }

    public function getProductData(MagentoProduct $product)
    {
        $data = array();
        try {
            $data = $this->_getProductData($product);
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
        return $data;
    }

    protected function _getProductData(MagentoProduct $product)
    {
        return array(
            'name'         => $this->getName($product),
            'brand'        => $this->getBrand($product),
            'sku_values'   => array($this->getSku($product)),
            'internal_id'  => "{$product->getId()}",
            'url'          => $this->getUrl($product),
            'image_url'    => $this->getImageUrl($product),
            'price'        => $this->getPrice($product),
            'currency'     => $this->getCurrency(),
            'category'     => $this->getCategory($product),
            'description'  => $this->getDescription($product),
            'availability' => $this->getAvailability($product)
        );
    }

    protected function getName(MagentoProduct $product)
    {
        return $this->filterText($product->getName());
    }

    protected function getBrand(MagentoProduct $product)
    {
        $brandAttr = $this->lipscoreConfig->brandAttr();
        $brand = $this->getAttributeValue($product, $brandAttr);
        return $this->filterText($brand);
    }

    public function getUrl(MagentoProduct $product)
    {
        return $product->getProductUrl(false);
    }

    protected function getImageUrl(MagentoProduct $product)
    {
        return $this->imageHelper->init($product, 'product_page_image_medium')->getUrl();
    }

    protected function getCategory(MagentoProduct $product)
    {
        $category = $this->registry->registry('current_category');
        if (!$category) {
            $categoryIds = $product->getCategoryIds();
            if (isset($categoryIds[0])) {
                $category = $this->catalogCategory->load($categoryIds[0]);
            }
        }
        return $category ? $category->getName() : '';
    }

    protected function getAvailability(MagentoProduct $product)
    {
        return (int) $product->getIsSalable();
    }

    protected function getDescription(MagentoProduct $product)
    {
        $description = $product->getShortDescription();
        if (!$description) {
            $description = $product->getDescription();
        }
        return $this->filterText($description);
    }

    protected function getPrice(MagentoProduct $product)
    {
        $finalPrice = $product->getPriceInfo()->getPrice(Price\FinalPrice::PRICE_CODE);
        return $finalPrice->getMinimalPrice()->getValue();
    }

    protected function getCurrency()
    {
        return $this->getStore()->getCurrentCurrency()->getCode();
    }

    protected function getSku(MagentoProduct $product)
    {
        $sku = $product->getSku();
        if (!$sku) {
            $sku = $this->productRepository->getById($product->getId())->getSku();
        }
        return $sku;
    }

    protected function getAttributeValue(MagentoProduct $product, $attrCode)
    {
        $attr = $product->getResource()->getAttribute($attrCode);
        if (!$attr) {
            return null;
        }

        if ('select' == $attr->getFrontendInput()) {
            return $attr->getSource()->getOptionText($product->getData($attrCode));
        } else {
            return $product->getData($attrCode);
        }
    }

    protected function filterText($text)
    {
        if (is_string($text)) {
            return html_entity_decode(strip_tags($text));
        } else {
            return $text;
        }
    }
}
