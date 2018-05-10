<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Helper\AbstractHelper;
use Magento\SalesRule\Model\Rule;
use Magento\Framework\Exception\LocalizedException;

class Coupon extends AbstractHelper
{
    protected $ruleFactory;
    protected $massgeneratorFactory;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Lipscore\RatingsReviews\Model\Config\AbstractConfig $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $massgeneratorFactory
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->massgeneratorFactory = $massgeneratorFactory;
    }

    public function acquireCouponCode(\Magento\SalesRule\Model\Rule $priceRule)
    {
        if ($priceRule->getCouponType() == Rule::COUPON_TYPE_NO_COUPON) {
            return;
        }

        if ($priceRule->getCouponType() == Rule::COUPON_TYPE_SPECIFIC && !$priceRule->getUseAutoGeneration()) {
            return $priceRule->getPrimaryCoupon()->getCode();
        }

        $generator = $this->massgeneratorFactory->create();
        $data = [
            'qty'                => 1,
            'rule_id'            => $priceRule->getId(),
            'length'             => 12,
            'format'             => \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC,
            'usage_per_customer' => $priceRule->getUsesPerCustomer() ? $priceRule->getUsesPerCustomer() : null,
            'usage_limit'        => $priceRule->getUsesPerCoupon() ? $priceRule->getUsesPerCoupon() : null,
            'to_date'            => $priceRule->getToDate()
        ];
       $generator->setData($data);
       $generatedCodes = $generator->generatePool()->getGeneratedCodes();
       return count($generatedCodes) > 0 ? $generatedCodes[0] : null;
    }
}
