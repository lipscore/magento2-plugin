<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Module;

use Magento\Framework\Data\OptionSourceInterface;

class Active implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Active')],
            ['value' => 0, 'label' => __('Inactive')],
        ];
    }
}
