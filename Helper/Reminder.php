<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;
use Magento\Sales\Model\Order;
use Magento\Framework\UrlInterface;

class Reminder extends AbstractHelper
{
    protected $ruleFactory;
    protected $productFactory;
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
        \Lipscore\RatingsReviews\Helper\ProductFactory $productHelperFactory,
        \Lipscore\RatingsReviews\Helper\LocaleFactory $localeHelperFactory,
        \Lipscore\RatingsReviews\Helper\CouponFactory $couponHelperFactory,
        \Lipscore\RatingsReviews\Helper\PurchaseFactory $purchaseHelperFactory,
        \Lipscore\RatingsReviews\Helper\Reminder\ProductType $productTypeHelper
    ){
        parent::__construct($logger, $config, $storeManager);

        $this->ruleFactory       = $ruleFactory;
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
        $couponData = $this->couponData($order);

        $email  = $this->purchaseHelper->customerEmail($order);
        $name   = $this->purchaseHelper->customerName($order);
        $lang   = $this->localeHelper->getStoreLocale();
        $date   = $this->purchaseHelper->createdAt($order);

        return array_merge($couponData, [
            'buyer_email'   => $email,
            'buyer_name'    => $name,
            'purchased_at'  => $date,
            'lang'          => $lang
        ]);
    }

    protected function couponData(\Magento\Sales\Model\Order $order)
    {
        $data = [];

        $priceRuleId = $this->lipscoreConfig->priceRuleId();
        if (!$priceRuleId) {
            return $data;
        }

        $priceRule = $this->ruleFactory->create()->load($priceRuleId);
        $coupon = $this->couponHelper->acquireCoupon($priceRule);

        if ($coupon) {
            $data['discount_descr']   = $priceRule->getDescription();
            $data['discount_voucher'] = $coupon->getCode();
        }

        return $data;
    }

    protected function productsData(\Magento\Sales\Model\Order $order)
    {
        $productsData = [];
        $storeId = $order->getStoreId();
        $orderItems = $order->getAllVisibleItems();

        foreach ($orderItems as $orderItem) {
            $productId = $orderItem->getProductId();
            $product   = $this->productFactory->create()->load($productId);

            $parentProductId = $this->getParentProductId($product, $orderItem);
            if ($parentProductId) {
                $product = $this->productFactory->create()->load($parentProductId);
            }

            $product->setStoreId($storeId);
            $data = $this->productHelper->getProductData($product);

            if (!$product->isVisibleInSiteVisibility() && !$parentProductId) {
                $store = $this->storeManager->getStore($storeId);
                $data['url'] = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
            }

            if ($data) {
                $productsData[$product->getId()] = $data;
            }

            gc_collect_cycles();
        }

        return array_values($productsData);
    }

    protected function getParentProductId($product, $item)
    {
        $superProductConfig = $item->getBuyRequest()->getSuperProductConfig();
        if (!empty($superProductConfig['product_id'])) {
            return (int) $superProductConfig['product_id'];
        }

        if ($product->isVisibleInSiteVisibility()) {
            return;
        }

        return $this->productTypeHelper->getParentId($product->getId());
    }
}
