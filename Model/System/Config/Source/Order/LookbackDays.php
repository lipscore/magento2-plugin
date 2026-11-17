<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Days the scheduled check looks back for orders.
 */
class LookbackDays implements OptionSourceInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('1 day')],
            ['value' => '7', 'label' => __('7 days')],
            ['value' => '14', 'label' => __('14 days')],
            ['value' => '30', 'label' => __('30 days')],
        ];
    }
}
