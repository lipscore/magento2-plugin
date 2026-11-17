<?php

namespace Lipscore\RatingsReviews\Model\Invitation;

/**
 * Invitation status, stored as a number in lipscore_invitation.status.
 */
class Status
{
    /** Lipscore created the invitation; Lipscore sends it to the customer later. */
    public const SCHEDULED = 1;

    /** Temporary error (timeout, server error, account problem); the scheduled check retries it. */
    public const FAILED = 2;

    /** Lipscore answered 409: the invitation exists or the customer is within a repeat-block period. Final. */
    public const ALREADY_SCHEDULED = 3;

    /** Lipscore rejected the invitation data (400, 422); not retried until scheduled again manually. */
    public const REJECTED = 4;

    /** Not stored: shown in the order grid for orders without a record. */
    public const NONE = 0;

    /** Statuses after which the order is never scheduled again automatically or by the bulk action. */
    public const FINAL_STATUSES = [self::SCHEDULED, self::ALREADY_SCHEDULED];

    /**
     * Get labels.
     *
     * @return array [status => label]
     */
    public function getLabels()
    {
        return [
            self::SCHEDULED => __('Scheduled'),
            self::ALREADY_SCHEDULED => __('Already scheduled'),
            self::FAILED => __('Failed'),
            self::REJECTED => __('Rejected'),
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
