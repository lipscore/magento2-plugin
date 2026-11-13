<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order;

use Lipscore\RatingsReviews\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class Trigger implements OptionSourceInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => Config::EMAILS_TRIGGER_EVENT, 'label' => __('On order save (real-time)')],
            ['value' => Config::EMAILS_TRIGGER_CRON, 'label' => __('Scheduled check (cron)')],
        ];
    }
}
