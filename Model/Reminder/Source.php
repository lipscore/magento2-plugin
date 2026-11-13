<?php

namespace Lipscore\RatingsReviews\Model\Reminder;

/**
 * What created a delivery log record, stored as a number in lipscore_order_reminder.source.
 */
class Source
{
    /** Real-time order status change (order save). */
    public const EVENT = 1;

    /** Scheduled order status check. */
    public const CRON = 2;

    /** Mass action in the sales order grid. */
    public const MANUAL = 3;

    /**
     * Get labels.
     *
     * @return array [source => label]
     */
    public function getLabels()
    {
        return [
            self::EVENT => __('on order status change'),
            self::CRON => __('by the scheduled check'),
            self::MANUAL => __('manually from the order grid'),
        ];
    }

    /**
     * Get label.
     *
     * @param int|string|null $source
     * @return \Magento\Framework\Phrase
     */
    public function getLabel($source)
    {
        $labels = $this->getLabels();

        return $labels[(int) $source] ?? __('Unknown (%1)', $source);
    }
}
