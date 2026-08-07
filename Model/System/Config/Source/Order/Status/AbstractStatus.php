<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order\Status;

use Lipscore\RatingsReviews\Model\Logger;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class AbstractStatus
{
    /**
     * @var CollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param Logger $logger
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        Logger $logger,
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->logger                  = $logger;
    }

    /**
     * Get order status options.
     *
     * @return array
     */
    protected function getStatusOptions()
    {
        return $this->statusCollectionFactory->create()->toOptionArray();
    }
}
