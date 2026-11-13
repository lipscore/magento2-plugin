<?php

namespace Lipscore\RatingsReviews\Cron;

use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Reminder\CronProcessor;

class SendReviewRequests
{
    /** @var CronProcessor */
    protected $processor;

    /** @var Logger */
    protected $logger;

    /**
     * @param CronProcessor $processor
     * @param Logger $logger
     */
    public function __construct(
        CronProcessor $processor,
        Logger $logger
    ) {
        $this->processor = $processor;
        $this->logger = $logger;
    }

    /**
     * Cron entry point.
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->processor->execute();
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
    }
}
