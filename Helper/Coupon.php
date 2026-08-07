<?php

namespace Lipscore\RatingsReviews\Helper;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\SalesRule\Model\Coupon\MassgeneratorFactory;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;

class Coupon extends AbstractHelper
{
    /**
     * @var mixed
     */
    protected $ruleFactory;

    /**
     * @var MassgeneratorFactory
     */
    protected $massgeneratorFactory;

    /**
     * Initialize dependencies.
     *
     * @param Logger $logger
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param MassgeneratorFactory $massgeneratorFactory
     */
    public function __construct(
        Logger $logger,
        Config $config,
        StoreManagerInterface $storeManager,
        MassgeneratorFactory $massgeneratorFactory
    ) {
        parent::__construct($logger, $config, $storeManager);

        $this->massgeneratorFactory = $massgeneratorFactory;
    }

    /**
     * Acquire a coupon code for the given price rule.
     *
     * @param \Magento\SalesRule\Model\Rule $priceRule
     * @return string|null
     */
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
