<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Model\Product\UrlFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Pricing\Price;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Registry;
use Magento\Framework\Url;
use Magento\Framework\UrlFactoryFactory;
use Magento\Store\Model\StoreManagerInterface;

class Product extends AbstractHelper
{
    protected $productRepository;

    protected $imageHelper;

    protected $registry;

    protected $catalogCategory;

    protected $urlModel;

    protected $productCache = [];

    protected $childProductCache = [];

    public function __construct(
        Logger $logger,
        Config $config,
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository,
        Image $imageHelper,
        Registry $registry,
        Category $catalogCategory,
        UrlFactory $urlModelFactory,
        UrlFactoryFactory $urlFactoryFactory
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->productRepository = $productRepository;
        $this->imageHelper       = $imageHelper;
        $this->registry          = $registry;
        $this->catalogCategory   = $catalogCategory;
        $this->urlModel          = $urlModelFactory->create(
            ['urlFactory' => $urlFactoryFactory->create(['instanceName' => Url::class])]
        );
    }

    public function getProductData(MagentoProduct $product, $withChildProducts = false)
    {
        $data = [];
        try {
            $data = $this->_getProductData($product);
            if ($withChildProducts) {
                $data = array_merge($data, $this->_getChildProductsData($product));
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
        return $data;
    }

    public function getProductFullData($parentProduct, $variant = null)
    {
        $data = [];
        try {
            $data = array_merge(
                $this->_getProductData($parentProduct),
                $this->_getVariantData($variant)
            );
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
        return $data;
    }

    protected function _getProductData(MagentoProduct $product)
    {
        if (!isset($this->productCache[$product->getId()])) {
            $this->productCache[$product->getId()] = [
                'name'         => $this->getName($product),
                'brand'        => $this->getBrand($product),
                'sku_values'   => [$this->getSku($product)],
                'internal_id'  => $this->getId($product),
                'url'          => $this->getUrl($product),
                'image_url'    => $this->getImageUrl($product),
                'price'        => $this->getPrice($product),
                'currency'     => $this->getCurrency($product),
                'category'     => $this->getCategory($product),
                'description'  => $this->getDescription($product),
                'availability' => $this->getAvailability($product),
                'gtin'         => $this->getGtin($product),
                'mpn'          => $this->getMpn($product)
            ];
        }

        return $this->productCache[$product->getId()];
    }

    protected function _getChildProductsData(MagentoProduct $product)
    {
        if (!isset($this->childProductCache[$product->getId()])) {
            $data = [];
            $productType = $product->getTypeId();
            if ($productType === Configurable::TYPE_CODE) {
                $children = $product->getTypeInstance()->getUsedProducts($product);
                $childGtins = $childMpns = $childSkus = [];
                foreach ($children as $child) {
                    $gtin = $this->getGtin($child);
                    if (!empty($gtin)) {
                        $childGtins = array_merge($childGtins, $gtin);
                    }
                    $childMpns[] = $this->getMpn($child);
                    $childSkus[] = $this->getSku($child);
                }

                if ($childGtins) {
                    $data['gtin'] = $childGtins;
                }

                if ($childMpns) {
                    $data['mpn'] = implode(Widget::WIDGET_SEPARATOR, $childMpns);
                }

                if ($childSkus) {
                    $data['sku_values'] = $childSkus;
                }
            }

            $this->childProductCache[$product->getId()] = $data;
        }

        return $this->childProductCache[$product->getId()];
    }

    protected function _getVariantData($product)
    {
        if (!$product) {
            return [];
        }

        $data = [
            'variant_id'   => $this->getId($product),
            'variant_name' => $this->getName($product),
        ];

        $mpn = $this->getMpn($product);
        if ($mpn) {
            $data['mpn'] = $mpn;
        }

        $gtin = $this->getGtin($product);
        if ($gtin) {
            $data['gtin'] = $gtin;
        }

        $sku = $this->getSku($product);
        if ($sku) {
            $data['sku_values'] = [$sku];
        }

        return $data;
    }

    protected function getName(MagentoProduct $product)
    {
        return $this->filterText($product->getName());
    }

    protected function getBrand(MagentoProduct $product)
    {
        $brandAttr = $this->config->getProductAttributeBrand($product->getStoreId());
        $brand = $this->getAttributeValue($product, $brandAttr);
        return $this->filterText($brand);
    }

    protected function getId(MagentoProduct $product)
    {
        $idAttr = $this->config->getProductAttributeId($product->getStoreId());
        $id = $this->getAttributeValue($product, $idAttr);
        return "{$id}";
    }

    protected function getGtin(MagentoProduct $product)
    {
        $gtinAttr = $this->config->getProductAttributeGtin($product->getStoreId());
        $gtin = $this->getAttributeValue($product, $gtinAttr);
        if (!$gtin) {
            return [];
        }
        $delimiters = array(",", "_", " ");
        $gtinArray = $this->multiExplode($delimiters, $gtin);

        return $gtinArray;
    }

    protected function getMpn(MagentoProduct $product)
    {
        $attr = $this->config->getProductAttributeMpn($product->getStoreId());

        return $this->getAttributeValue($product, $attr) ?? '';
    }

    public function multiExplode ($delimiters, $data)
    {
        $data          = isset($data) ? $data : '';
        $processedData = str_replace($delimiters, $delimiters[0], $data);
        $return        = explode($delimiters[0], $processedData);

        return $return;
    }

    public function getUrl(MagentoProduct $product)
    {
        return $this->urlModel->getUrl(
            $product,
            [
                '_ls_remove_scope' => true,
                '_nosid'           => true
            ]
        );
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
        return $this->filterText($category ? $category->getName() : '');
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

    protected function getCurrency(MagentoProduct $product)
    {
        return $this->storeManager->getStore($product->getStoreId())
            ->getCurrentCurrency()
            ->getCode();
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
        if (!$attrCode) {
            return null;
        }

        if ($attrCode === 'id') {
            return $product->getId();
        }

        if ($attrCode === 'sku') {
            return $this->getSku($product);
        }

        $attr = $product->getResource()->getAttribute($attrCode);

        if (!$attr) {
            return null;
        }

        if ('select' === $attr->getFrontendInput()) {
            $value = $attr->getSource()->getOptionText($product->getData($attrCode));
        } else {
            $value =  $product->getData($attrCode);
        }

        return $value ?: null;
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
