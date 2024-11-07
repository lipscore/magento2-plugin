<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\UrlInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Reminder extends AbstractHelper
{
    protected $ruleFactory;
    protected $productFactory;
    protected $productRepository;
    protected $productHelper;
    protected $localeHelper;
    protected $couponHelper;
    protected $purchaseHelper;
    protected $productTypeHelper;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Model\Config\AbstractConfig $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        \Lipscore\RatingsReviews\Helper\ProductFactory $productHelperFactory,
        \Lipscore\RatingsReviews\Helper\LocaleFactory $localeHelperFactory,
        \Lipscore\RatingsReviews\Helper\CouponFactory $couponHelperFactory,
        \Lipscore\RatingsReviews\Helper\PurchaseFactory $purchaseHelperFactory,
        \Lipscore\RatingsReviews\Helper\Reminder\ProductType $productTypeHelper
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->ruleFactory       = $ruleFactory;
        $this->productRepository = $productRepository;
        $this->productFactory    = $productFactory;
        $this->productTypeHelper = $productTypeHelper;
        $this->productHelper     = $productHelperFactory->create(['config' => $config]);
        $this->localeHelper      = $localeHelperFactory->create(['config' => $config]);
        $this->couponHelper      = $couponHelperFactory->create(['config' => $config]);
        $this->purchaseHelper    = $purchaseHelperFactory->create(['config' => $config]);
    }

    public function data(\Magento\Sales\Model\Order $order)
    {
        $data = [];
        return [
            'purchase' => $this->purchaseData($order),
            'products' => $this->productsData($order)
        ];
    }

    protected function purchaseData(\Magento\Sales\Model\Order $order)
    {
        $couponData = $this->couponData();

        $email      = $this->purchaseHelper->customerEmail($order);
        $name       = $this->purchaseHelper->customerName($order);
        $lang       = $this->localeHelper->getStoreLocale();
        $date       = $this->purchaseHelper->createdAt($order);
        $customerId = $order->getCustomerId() ? $order->getCustomerId() : '';

        return array_merge(
            $couponData,
            [
                'internal_order_id'    => (string) $order->getIncrementId(),
                'internal_customer_id' => $customerId,
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

    protected function productsData(\Magento\Sales\Model\Order $order)
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
            $product = $mainProduct = $this->productRepository->getById($productId, false, $storeId);

            $parentProductId = $parentItem ? $parentItem->getProductId() : null;
            if ($parentProductId) {
                $mainProduct = $this->productRepository->getById($parentProductId, false, $storeId);
            }

            $product->setStoreId($storeId);
            $mainProduct->setStoreId($storeId);

            $variantProduct = $parentItem && $parentItem->getProductType() == Configurable::TYPE_CODE ? $product : null;
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

}
