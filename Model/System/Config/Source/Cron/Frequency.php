<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Cron;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Values are cron expressions, read via <config_path> in crontab.xml.
 */
class Frequency implements OptionSourceInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '*/5 * * * *', 'label' => __('5 minutes')],
            ['value' => '*/10 * * * *', 'label' => __('10 minutes')],
            ['value' => '*/15 * * * *', 'label' => __('15 minutes')],
            ['value' => '*/30 * * * *', 'label' => __('30 minutes')],
            ['value' => '0 * * * *', 'label' => __('60 minutes')],
        ];
    }
}
