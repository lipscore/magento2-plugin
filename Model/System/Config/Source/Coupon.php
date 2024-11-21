<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source;

use Lipscore\RatingsReviews\Model\Logger;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\Rule;

class Coupon implements OptionSourceInterface
{
    protected $ruleCollection;

    protected $logger;

    public function __construct(
        Logger $logger,
        Collection $ruleCollection
    ) {
        $this->ruleCollection = $ruleCollection;
        $this->logger         = $logger;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => '', 'label' => __('--Please Select--')]
        ];

        try {
            $attrs = $this->findPriceRules();
            foreach ($attrs as $attr) {
                $options[] = [
                    'value' => $attr->getId(),
                    'label' => $attr->getName()
                ];
            }
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $options;
    }

    protected function findPriceRules()
    {
        $this->ruleCollection
            ->addIsActiveFilter()
            ->addFieldToFilter(
                'coupon_type',
                [
                    'in' => [
                        Rule::COUPON_TYPE_AUTO,
                        Rule::COUPON_TYPE_SPECIFIC
                    ]
                ]
            )
            ->addOrder('name', Collection::SORT_ORDER_ASC);
        $this->ruleCollection->load();
        return $this->ruleCollection;
    }
}
