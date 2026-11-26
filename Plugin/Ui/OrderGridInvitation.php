<?php

namespace Lipscore\RatingsReviews\Plugin\Ui;

use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Invitation\Status;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation as InvitationResource;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;

/**
 * Adds the Lipscore invitation status and date to the sales order grid.
 */
class OrderGridInvitation
{
    public const REQUEST_NAME = 'sales_order_grid_data_source';
    public const TABLE_ALIAS = 'lipscore_reminder';

    public const FIELD_STATUS = 'lipscore_invitation_status';
    public const FIELD_DATE = 'lipscore_invitation_date';

    /** @var InvitationResource */
    protected $invitationResource;

    /** @var Logger */
    protected $logger;

    /**
     * @param InvitationResource $invitationResource
     * @param Logger $logger
     */
    public function __construct(
        InvitationResource $invitationResource,
        Logger $logger
    ) {
        $this->invitationResource = $invitationResource;
        $this->logger = $logger;
    }

    /**
     * Join the invitation log.
     *
     * @param CollectionFactory $subject
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @param string $requestName
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function afterGetReport(CollectionFactory $subject, $collection, $requestName)
    {
        if ($requestName !== self::REQUEST_NAME) {
            return $collection;
        }

        try {
            $connection = $collection->getConnection();
            $table = $this->invitationResource->getMainTable();
            $select = $collection->getSelect();

            // Keep the grid working before setup:upgrade creates the table.
            if (!$connection->isTableExists($table) || isset($select->getPart(Select::FROM)[self::TABLE_ALIAS])) {
                return $collection;
            }

            $statusExpression = new Expression(sprintf(
                'IFNULL(%s.status, %d)',
                self::TABLE_ALIAS,
                Status::NONE
            ));
            $dateField = self::TABLE_ALIAS . '.updated_at';

            $select->joinLeft(
                [self::TABLE_ALIAS => $table],
                self::TABLE_ALIAS . '.order_id = main_table.entity_id',
                [self::FIELD_STATUS => $statusExpression, self::FIELD_DATE => $dateField]
            );
            $collection->addFilterToMap(self::FIELD_STATUS, $statusExpression);
            $collection->addFilterToMap(self::FIELD_DATE, $dateField);
            $this->mapSharedColumns($collection, $table);
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $collection;
    }

    /**
     * Qualify grid columns that also exist in the invitation table.
     *
     * Filters such as the purchase date one use bare column names, which are
     * ambiguous after the join (created_at, updated_at, store_id, status).
     *
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @param string $table
     * @return void
     */
    protected function mapSharedColumns($collection, $table)
    {
        $connection = $collection->getConnection();
        $from = $collection->getSelect()->getPart(Select::FROM);
        if (empty($from['main_table']['tableName'])) {
            return;
        }

        $gridColumns = array_keys($connection->describeTable($from['main_table']['tableName']));
        $invitationColumns = array_keys($connection->describeTable($table));
        foreach (array_intersect($gridColumns, $invitationColumns) as $column) {
            $collection->addFilterToMap($column, 'main_table.' . $column);
        }
    }
}
