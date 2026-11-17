<?php

namespace Lipscore\RatingsReviews\Model\Invitation;

/**
 * What scheduled the invitation, stored as a number in lipscore_invitation.source.
 */
class Source
{
    /** Real-time order status change (order save). */
    public const EVENT = 1;

    /** Scheduled order status check. */
    public const CRON = 2;

    /** Bulk action in the sales order grid. */
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
            self::CRON => __('by the hourly check'),
            self::MANUAL => __('by the bulk action in the order grid'),
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
