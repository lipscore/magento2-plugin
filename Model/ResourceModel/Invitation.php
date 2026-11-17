<?php

namespace Lipscore\RatingsReviews\Model\ResourceModel;

use Lipscore\RatingsReviews\Model\Invitation\Status;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Invitation log, one row per order.
 */
class Invitation extends AbstractDb
{
    public const TABLE = 'lipscore_invitation';

    /** Length of the message column content. */
    private const MAX_MESSAGE_LENGTH = 2000;

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
     * Whether the order's invitation is scheduled (or Lipscore reported it as already scheduled).
     *
     * @param int $orderId
     * @return bool
     */
    public function isScheduled($orderId)
    {
        return (bool) $this->getScheduledOrderIds([$orderId]);
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
     * Filter the given orders to the ones with a scheduled invitation.
     *
     * @param int[] $orderIds
     * @return int[]
     */
    public function getScheduledOrderIds(array $orderIds)
    {
        if (!$orderIds) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['order_id'])
            ->where('order_id IN (?)', array_map('intval', $orderIds))
            ->where('status IN (?)', Status::FINAL_STATUSES);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Record results, one query for all orders.
     *
     * Only FAILED results of the scheduled check count towards its retry limit (cron_attempts).
     *
     * @param array[] $results Rows with order_id, store_id, status, source, message and cron_attempt (bool)
     * @return void
     */
    public function saveResults(array $results)
    {
        if (!$results) {
            return;
        }

        $rows = [];
        foreach ($results as $result) {
            $message = isset($result['message']) ? (string) $result['message'] : '';
            $rows[] = [
                'order_id' => (int) $result['order_id'],
                'store_id' => (int) $result['store_id'],
                'status' => (int) $result['status'],
                'source' => (int) $result['source'],
                'attempts' => 1,
                'cron_attempts' => empty($result['cron_attempt']) ? 0 : 1,
                'message' => $message !== '' ? mb_substr($message, 0, self::MAX_MESSAGE_LENGTH) : null,
            ];
        }

        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            $rows,
            [
                'store_id',
                'status',
                'source',
                'message',
                'attempts' => new Expression('attempts + 1'),
                'cron_attempts' => new Expression('cron_attempts + VALUES(cron_attempts)'),
            ]
        );
    }

    /**
     * Orders in the status that the hourly check should send.
     *
     * Orders without a record (when $includeNew), plus failed ones due for retry per $retryDueBefore.
     *
     * @param int $storeId
     * @param string $orderStatus
     * @param string $updatedSince UTC datetime (Y-m-d H:i:s)
     * @param string[] $retryDueBefore [cron_attempts => UTC datetime the last failure must be before]
     * @param int $limit
     * @param bool $includeNew False to only retry failed orders (real-time mode)
     * @return int[]
     */
    public function getPendingOrderIds(
        $storeId,
        $orderStatus,
        $updatedSince,
        array $retryDueBefore,
        $limit,
        $includeNew = true
    ) {
        $connection = $this->getConnection();

        $pending = $includeNew ? ['lr.order_id IS NULL'] : [];
        foreach ($retryDueBefore as $attempts => $dueBefore) {
            $pending[] = $connection->quoteInto(
                sprintf('(lr.status = %d AND lr.cron_attempts = %d AND lr.updated_at <= ?)', Status::FAILED, $attempts),
                $dueBefore
            );
        }
        if (!$pending) {
            return [];
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
