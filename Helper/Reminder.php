<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\Coupon as CouponHelper;
use Lipscore\RatingsReviews\Helper\Locale as LocaleHelper;
use Lipscore\RatingsReviews\Helper\Product as ProductHelper;
use Lipscore\RatingsReviews\Helper\Purchase as PurchaseHelper;
use Lipscore\RatingsReviews\Helper\Reminder\ProductType as ProductTypeHelper;
use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;

class Reminder extends AbstractHelper
{
    protected $ruleFactory;

    protected $productRepository;

    protected $productHelper;

    protected $localeHelper;

    protected $couponHelper;

    protected $purchaseHelper;

    protected $productTypeHelper;

    public function __construct(
        Logger $logger,
        Config $config,
        StoreManagerInterface $storeManager,
        RuleFactory $ruleFactory,
        ProductRepositoryInterface $productRepository,
        ProductHelper $productHelper,
        LocaleHelper $localeHelper,
        CouponHelper $couponHelper,
        PurchaseHelper $purchaseHelper,
        ProductTypeHelper $productTypeHelper
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->ruleFactory       = $ruleFactory;
        $this->productRepository = $productRepository;
        $this->productTypeHelper = $productTypeHelper;
        $this->productHelper     = $productHelper;
        $this->localeHelper      = $localeHelper;
        $this->couponHelper      = $couponHelper;
        $this->purchaseHelper    = $purchaseHelper;
    }

    public function data(Order $order)
    {
        return [
            'purchase' => $this->purchaseData($order),
            'products' => $this->productsData($order)
        ];
    }

    protected function purchaseData(Order $order)
    {
        $couponData = $this->couponData();

        $email      = $this->purchaseHelper->customerEmail($order);
        $name       = $this->purchaseHelper->customerName($order);
        $lang       = $this->localeHelper->getLipscoreLocale($order->getStoreId());
        $date       = $this->purchaseHelper->createdAt($order);
        $customerId = $order->getCustomerId() ? $order->getCustomerId() : '';

        return array_merge(
            $couponData,
            [
                'internal_order_id' => (string) $order->getIncrementId(),
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

        $priceRuleId = $this->config->getCouponsPriceRuleId();
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
