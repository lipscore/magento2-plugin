<?php

namespace Lipscore\RatingsReviews\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonologLogger;

class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/lipscore_exception.log';

    /**
     * @var int
     */
    protected $loggerType = MonologLogger::ERROR;
}
