<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;
use Magento\SalesRule\Model\Rule;
use Magento\Framework\Exception\LocalizedException;

class Coupon extends AbstractHelper
{
    protected $ruleFactory;
    protected $couponFactory;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Model\Config\AbstractConfig $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SalesRule\Model\CouponFactory $couponFactory
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->couponFactory = $couponFactory;
    }

    public function acquireCoupon(\Magento\SalesRule\Model\Rule $priceRule)
    {
        if ($priceRule->getCouponType() == Rule::COUPON_TYPE_NO_COUPON) {
            return;
        }
        if ($priceRule->getCouponType() == Rule::COUPON_TYPE_SPECIFIC && !$priceRule->getUseAutoGeneration()) {
            return $priceRule->getPrimaryCoupon();
        }

        $coupon = $this->couponFactory->create();
        $coupon->setRule(
            $priceRule
        )->setIsPrimary(
            false
        )->setUsageLimit(
            $priceRule->getUsesPerCoupon() ? $priceRule->getUsesPerCoupon() : null
        )->setUsagePerCustomer(
            $priceRule->getUsesPerCustomer() ? $priceRule->getUsesPerCustomer() : null
        )->setExpirationDate(
            $priceRule->getToDate()
        );

        $couponCode = $priceRule->getCouponCodeGenerator()->generateCode();
        $coupon->setCode($couponCode);

        $ok = false;

        if ($priceRule->getId()) {
            for ($attemptNum = 0; $attemptNum < 10; $attemptNum++) {
                try {
                    $coupon->save();
                } catch (\Exception $e) {
                    if ($e instanceof LocalizedException || $coupon->getId()) {
                        throw $e;
                    }
                    $coupon->setCode(
                        $couponCode . $priceRule->getCouponCodeGenerator()->getDelimiter() . sprintf(
                            '%04u',
                            rand(0, 9999)
                        )
                    );
                    continue;
                }
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            $coupon = null;
            $e = new LocalizedException(__('Can\'t acquire coupon.'));
            $this->logger->log($e);
        }

        return $coupon;
    }
}
