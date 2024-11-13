<?php

namespace Lipscore\RatingsReviews\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonologLogger;

class Handler extends Base
{
    protected $fileName = '/var/log/lipscore_exception.log';

    protected $loggerType = MonologLogger::ERROR;
}
