<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\Model\AbstractModel;

class Queue extends AbstractModel
{
    public const STATUS_DONE = 1;
    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 2;
    public const STATUS_FAILED = 3;

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Queue::class);
    }

    public function getQueueId(): int
    {
        return (int)$this->getData('queue_id');
    }

    public function getProductId(): int
    {
        return (int)$this->getData('product_id');
    }

    public function getStatus(): string
    {
        return (string)$this->getData('status');
    }

    public function getKeyId(): string
    {
        return (int)$this->getData('key_id');
    }

    public function getRetries(): int
    {
        return (int)$this->getData('retries');
    }
}
