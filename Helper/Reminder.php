<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;
use Lipscore\RatingsReviews\Helper\CouponFactory as HelperCouponFactory;
use Lipscore\RatingsReviews\Helper\LocaleFactory as HelperLocaleFactory;
use Lipscore\RatingsReviews\Helper\ProductFactory as HelperProductFactory;
use Lipscore\RatingsReviews\Helper\PurchaseFactory as PurchaseFactoryAlias;
use Lipscore\RatingsReviews\Helper\Reminder\ProductType;
use Lipscore\RatingsReviews\Model\Config\AbstractConfig;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Catalog\Model\ProductFactory as ModelProductFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\UrlInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Reminder extends AbstractHelper
{
    const XML_PATH_MERGEREVIEWS_IS_ENABLED = 'mergereviews/general/is_enabled';

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;
    /**
     * @var ModelProductFactory
     */
    protected $productFactory;
    /**
     * @var Product
     */
    protected $productHelper;
    /**
     * @var Locale
     */
    protected $localeHelper;
    /**
     * @var Coupon
     */
    protected $couponHelper;
    /**
     * @var Purchase
     */
    protected $purchaseHelper;
    /**
     * @var ProductType
     */
    protected $productTypeHelper;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Logger $logger
     * @param AbstractConfig $config
     * @param StoreManagerInterface $storeManager
     * @param RuleFactory $ruleFactory
     * @param ModelProductFactory $productFactory
     * @param ProductFactory $productHelperFactory
     * @param LocaleFactory $localeHelperFactory
     * @param CouponFactory $couponHelperFactory
     * @param PurchaseFactory $purchaseHelperFactory
     * @param ProductType $productTypeHelper
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Logger $logger,
        AbstractConfig $config,
        StoreManagerInterface $storeManager,
        RuleFactory $ruleFactory,
        ModelProductFactory $productFactory,
        HelperProductFactory $productHelperFactory,
        HelperLocaleFactory $localeHelperFactory,
        HelperCouponFactory $couponHelperFactory,
        PurchaseFactoryAlias $purchaseHelperFactory,
        ProductType $productTypeHelper,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->ruleFactory = $ruleFactory;
        $this->productFactory = $productFactory;
        $this->productTypeHelper = $productTypeHelper;
        $this->productHelper = $productHelperFactory->create(['config' => $config]);
        $this->localeHelper = $localeHelperFactory->create(['config' => $config]);
        $this->couponHelper = $couponHelperFactory->create(['config' => $config]);
        $this->purchaseHelper = $purchaseHelperFactory->create(['config' => $config]);
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
    }

    public function data(Order $order)
    {
        $data = [];
        return [
            'purchase' => $this->purchaseData($order),
            'products' => $this->productsData($order)
        ];
    }

    protected function purchaseData(Order $order)
    {
        $couponData = $this->couponData();

        $email  = $this->purchaseHelper->customerEmail($order);
        $name   = $this->purchaseHelper->customerName($order);
        $lang   = $this->localeHelper->getStoreLocale();
        $date   = $this->purchaseHelper->createdAt($order);

        return array_merge(
            $couponData,
            [
                'buyer_email'   => $email,
                'buyer_name'    => $name,
                'purchased_at'  => $date,
                'lang'          => $lang
            ]
        );
    }

    protected function couponData()
    {
        $data = [];

        $priceRuleId = $this->lipscoreConfig->priceRuleId();
        if (!$priceRuleId) {
            return $data;
        }

        $priceRule = $this->ruleFactory->create()->load($priceRuleId);
        $couponCode = $this->couponHelper->acquireCouponCode($priceRule);

        if ($couponCode) {
            $data['discount_descr']   = $priceRule->getDescription();
            $data['discount_voucher'] = $couponCode;
        }

        return $data;
    }

    /**
     * @param Order $order
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function productsData(Order $order)
    {
        $productsData = [];
        $storeId = $order->getStoreId();
        $orderItems = $order->getAllVisibleItems();

        foreach ($orderItems as $orderItem) {
            if ($orderItem->getProductType() == Configurable::TYPE_CODE) {
                continue;
            }

            $parentItem = $orderItem->getParentItem();
            $productId = $orderItem->getProductId();
            $product = $mainProduct = $this->productFactory->create()->load($productId);

            $parentProductId = $parentItem ? $parentItem->getProductId() : null;
            $parentType = $parentItem && $parentItem->getProductType() ? $parentItem->getProductType() : null;
            if ($parentProductId) {
                $mainProduct = $this->productFactory->create()->load($parentProductId);
            }elseif ($this->isEnabled()) {
                $ids = $this->getParentIds($orderItem->getProductId());
                if (!empty($ids)) {
                    $parentId = $ids[0];
                    $mainProduct = $this->productFactory->create()->load($parentId);
                    $parentType = $mainProduct->getTypeId();
                }
            }

            $product->setStoreId($storeId);
            $mainProduct->setStoreId($storeId);

            $variantProduct = $parentType == Configurable::TYPE_CODE ? $product : null;
            $data = $this->productHelper->getProductFullData($mainProduct, $variantProduct);

            if (!$product->isVisibleInSiteVisibility() && !$parentProductId) {
                $store = $this->storeManager->getStore($storeId);
                $data['url'] = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
            }

            if ($data) {
                $dataProductId = $variantProduct ? $variantProduct->getId() : $mainProduct->getId();
                $productsData[$dataProductId] = $data;
            }

            gc_collect_cycles();
        }

        return array_values($productsData);
    }

    protected function getParentProductId($product, $item)
    {
        return $item->getParentItem() ? $item->getParentItem()->getProductId() : null;
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

        return (bool)$this->scopeConfig->getValue(self::XML_PATH_MERGEREVIEWS_IS_ENABLED, $storeScope);
    }
}
