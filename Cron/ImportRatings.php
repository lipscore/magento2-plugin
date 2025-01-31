<?php

namespace Lipscore\RatingsReviews\Cron;

use Lipscore\RatingsReviews\Model\Action\ImportRatings as ImportRatingsAction;

class ImportRatings
{
    protected $importRatingsAction;

    public function __construct(
        ImportRatingsAction $importRatingsAction
    ) {
        $this->importRatingsAction = $importRatingsAction;
    }
    public function execute()
    {
        $this->importRatingsAction->process();
    }
}
