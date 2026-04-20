<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;

class QueueRepository
{
    private const TABLE = 'lipscore_queue';
    private const MAX_RETRIES = 3;

    public const PRIORITY_HIGH = 5;
    public const PRIORITY_LOW  = 1;

    protected $resource;

    protected $logger;

    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->resource         = $resource;
        $this->logger           = $logger;
    }

    private function connection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }

    private function table(): string
    {
        return $this->resource->getTableName(self::TABLE);
    }

    public function enqueue(int $productId, ?int $keyId, int $priority = self::PRIORITY_LOW): void
    {
        if (!$keyId) {
            return;
        }

        $conn  = $this->connection();
        $table = $this->table();

        try {
            $conn->insertOnDuplicate(
                $table,
                [
                    'product_id' => $productId,
                    'key_id'     => $keyId,
                    'status'     => Queue::STATUS_PENDING,
                    'priority'   => $priority,
                    'retries'    => 0,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                ],
                [] // do nothing on duplicate
            );
        } catch (\Throwable $e) {
            $this->logger->error('Queue enqueue failed', [
                'product_id' => $productId,
                'key_id'     => $keyId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function dequeue(?int $keyId, int $batchSize = 20): array
    {
        if (!$keyId) {
            return [];
        }

        $conn  = $this->connection();
        $table = $this->table();

        $conn->beginTransaction();

        try {
            $select = $conn->select()
                ->from($table)
                ->where('status = ?', Queue::STATUS_PENDING)
                ->where('retries < ?', self::MAX_RETRIES)
                ->where('key_id = ?', $keyId)
                ->order('priority DESC')
                ->order('created_at ASC')
                ->limit($batchSize)
                ->forUpdate();

            $rows = $conn->fetchAll($select);

            if (!$rows) {
                $conn->commit();
                return [];
            }

            $ids = array_column($rows, 'queue_id');

            $conn->update(
                $table,
                ['status' => Queue::STATUS_PROCESSING],
                ['queue_id IN (?)' => $ids]
            );

            $conn->commit();

            return $rows;

        } catch (\Throwable $e) {
            $conn->rollBack();
            $this->logger->error('Queue dequeue failed', [
                'key_id' => $keyId,
                'error'  => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function markProcessed(int $queueId, bool $success): void
    {
        $conn  = $this->connection();
        $table = $this->table();

        if ($success) {
            $conn->delete(
                $table,
                ['status' => Queue::STATUS_DONE],
                ['queue_id = ?' => $queueId]
            );
            return;
        }

        $conn->query(
            sprintf(
                'UPDATE %s
                 SET retries = retries + 1,
                     status = IF(retries + 1 >= ?, ?, ?)
                 WHERE queue_id = ?',
                $table
            ),
            [
                self::MAX_RETRIES,
                Queue::STATUS_FAILED,
                Queue::STATUS_PENDING,
                $queueId
            ]
        );
    }

    public function cleanup(int $days = 3): int
    {
        $conn  = $this->connection();
        $table = $this->table();

        return $conn->delete(
            $table,
            [
                'status IN (?)' => [Queue::STATUS_DONE, Queue::STATUS_FAILED],
                'updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)' => $days,
            ]
        );
    }
}
