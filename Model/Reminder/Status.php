<?php

namespace Lipscore\RatingsReviews\Model\Reminder;

/**
 * Review request delivery status, stored as a number in lipscore_order_reminder.status.
 */
class Status
{
    public const SENT = 1;
    public const FAILED = 2;

    /** Not stored: shown in the order grid for orders without a record. */
    public const NONE = 0;

    /**
     * Get labels.
     *
     * @return array [status => label]
     */
    public function getLabels()
    {
        return [
            self::SENT => __('Sent'),
            self::FAILED => __('Failed'),
        ];
    }

    /**
     * Get label.
     *
     * @param int|string|null $status
     * @return \Magento\Framework\Phrase
     */
    public function getLabel($status)
    {
        if ($status === null || (int) $status === self::NONE) {
            return __('Not recorded');
        }

        $labels = $this->getLabels();

        return $labels[(int) $status] ?? __('Unknown (%1)', $status);
    }
}
