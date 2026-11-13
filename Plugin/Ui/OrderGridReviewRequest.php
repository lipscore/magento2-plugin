<?php

namespace Lipscore\RatingsReviews\Plugin\Ui;

use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Reminder\Status;
use Lipscore\RatingsReviews\Model\ResourceModel\OrderReminder as OrderReminderResource;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;

/**
 * Adds the Lipscore review request status and date to the sales order grid.
 */
class OrderGridReviewRequest
{
    public const REQUEST_NAME = 'sales_order_grid_data_source';
    public const TABLE_ALIAS = 'lipscore_reminder';

    public const FIELD_STATUS = 'lipscore_review_status';
    public const FIELD_DATE = 'lipscore_review_date';

    /** @var OrderReminderResource */
    protected $orderReminderResource;

    /** @var Logger */
    protected $logger;

    /**
     * @param OrderReminderResource $orderReminderResource
     * @param Logger $logger
     */
    public function __construct(
        OrderReminderResource $orderReminderResource,
        Logger $logger
    ) {
        $this->orderReminderResource = $orderReminderResource;
        $this->logger = $logger;
    }

    /**
     * Join the delivery log.
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
            $table = $this->orderReminderResource->getMainTable();
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
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $collection;
    }
}
