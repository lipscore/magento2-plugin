<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source;

use \Magento\SalesRule\Model\ResourceModel\Rule\Collection;

class Coupon implements \Magento\Framework\Option\ArrayInterface
{
    protected $ruleCollection;
    protected $logger;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        Collection $ruleCollection
    ){
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
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO,
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                    ]
                ]
           )
           ->addOrder('name', Collection::SORT_ORDER_ASC);
       $this->ruleCollection->load();
       return $this->ruleCollection;
    }
}
