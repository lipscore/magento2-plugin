<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order\Status;

use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class AbstractStatus
{
    protected $statusCollectionFactory;
    protected $logger;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory
    ){
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->logger                  = $logger;
    }

    protected function getStatusOptions()
    {
        return $this->statusCollectionFactory->create()->toOptionArray();
    }
}
