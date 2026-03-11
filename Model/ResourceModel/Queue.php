<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('lipscore_queue', 'queue_id');
    }
}
