<?php

namespace Lipscore\RatingsReviews\Model\ResourceModel;

use Lipscore\RatingsReviews\Model\Reminder\Source;
use Lipscore\RatingsReviews\Model\Reminder\Status;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Review request delivery log, one row per order.
 */
class OrderReminder extends AbstractDb
{
    public const TABLE = 'lipscore_order_reminder';

    /**
     * Initialize resource.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, 'order_id');
    }

    /**
     * Whether the order was sent.
     *
     * @param int $orderId
     * @return bool
     */
    public function isSent($orderId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['status'])
            ->where('order_id = ?', (int) $orderId);
        $status = $connection->fetchOne($select);

        return $status !== false && (int) $status === Status::SENT;
    }

    /**
     * Get the order's record.
     *
     * @param int $orderId
     * @return array|null
     */
    public function getRecord($orderId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('order_id = ?', (int) $orderId);
        $row = $connection->fetchRow($select);

        return $row ?: null;
    }

    /**
     * Filter the given orders to the sent ones.
     *
     * @param int[] $orderIds
     * @return int[]
     */
    public function getSentOrderIds(array $orderIds)
    {
        if (!$orderIds) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['order_id'])
            ->where('order_id IN (?)', array_map('intval', $orderIds))
            ->where('status = ?', Status::SENT);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Record a sent request.
     *
     * @param int $orderId
     * @param int $storeId
     * @param int $source One of the Source constants
     * @return void
     */
    public function markSent($orderId, $storeId, $source)
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'order_id' => (int) $orderId,
                'store_id' => (int) $storeId,
                'status' => Status::SENT,
                'source' => (int) $source,
                'attempts' => 1,
                'last_error' => null,
            ],
            [
                'status',
                'source',
                'last_error',
                'attempts' => new Expression('attempts + 1'),
            ]
        );
    }

    /**
     * Record a failed request; only cron failures count towards the cron retry limit.
     *
     * @param int $orderId
     * @param int $storeId
     * @param int $source One of the Source constants
     * @param string $error
     * @return void
     */
    public function markFailed($orderId, $storeId, $source, $error)
    {
        $cronAttempt = (int) $source === Source::CRON ? 1 : 0;

        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'order_id' => (int) $orderId,
                'store_id' => (int) $storeId,
                'status' => Status::FAILED,
                'source' => (int) $source,
                'attempts' => 1,
                'cron_attempts' => $cronAttempt,
                'last_error' => mb_substr((string) $error, 0, 2000),
            ],
            [
                'status',
                'source',
                'last_error',
                'attempts' => new Expression('attempts + 1'),
                'cron_attempts' => new Expression('cron_attempts + ' . $cronAttempt),
            ]
        );
    }

    /**
     * Orders without a record, plus failed ones due for retry per $retryDueBefore.
     *
     * @param int $storeId
     * @param string $orderStatus
     * @param string $updatedSince UTC datetime (Y-m-d H:i:s)
     * @param string[] $retryDueBefore [cron_attempts => UTC datetime the last failure must be before]
     * @param int $limit
     * @return int[]
     */
    public function getPendingOrderIds($storeId, $orderStatus, $updatedSince, array $retryDueBefore, $limit)
    {
        $connection = $this->getConnection();

        $pending = ['lr.order_id IS NULL'];
        foreach ($retryDueBefore as $attempts => $dueBefore) {
            $pending[] = $connection->quoteInto(
                sprintf('(lr.status = %d AND lr.cron_attempts = %d AND lr.updated_at <= ?)', Status::FAILED, $attempts),
                $dueBefore
            );
        }

        $select = $connection->select()
            ->from(['so' => $this->getTable('sales_order')], ['entity_id'])
            ->joinLeft(['lr' => $this->getMainTable()], 'lr.order_id = so.entity_id', [])
            ->where('so.store_id = ?', (int) $storeId)
            ->where('so.status = ?', (string) $orderStatus)
            ->where('so.updated_at >= ?', $updatedSince)
            ->where(implode(' OR ', $pending))
            ->order('so.entity_id ASC')
            ->limit((int) $limit);

        return array_map('intval', $connection->fetchCol($select));
    }
}
