<?php

namespace Lipscore\RatingsReviews\Api;

use Magento\Framework\Exception\LocalizedException;

interface WebhookManagementInterface
{
    /**
     * @return bool
     * @throws LocalizedException
     */
    public function handle(): bool;
}
