<?php

namespace Lipscore\RatingsReviews\Cron;

use Lipscore\RatingsReviews\Model\Action\ProcessLipscoreQueue as Action;
use Lipscore\RatingsReviews\Model\QueueRepository;
use Magento\Cron\Model\Schedule;

class ProcessQueue
{
    protected $action;

    protected $queueRepository;

    public function __construct(
        Action $action,
        QueueRepository $queueRepository
    ) {
        $this->action = $action;
        $this->queueRepository = $queueRepository;
    }

    public function execute(Schedule $schedule): void
    {
        $this->action->execute();
    }

    public function cleanup(Schedule $schedule): void
    {
        $this->queueRepository->cleanup();
    }
}
