<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order\Status;

use Lipscore\RatingsReviews\Model\Logger;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class AbstractStatus
{
    protected $statusCollectionFactory;

    protected $logger;

    public function __construct(
        Logger $logger,
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->logger                  = $logger;
    }

    protected function getStatusOptions()
    {
        return $this->statusCollectionFactory->create()->toOptionArray();
    }
}
