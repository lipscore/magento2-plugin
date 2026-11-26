<?php

namespace Lipscore\RatingsReviews\Test\Unit\Plugin\Ui;

use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation as InvitationResource;
use Lipscore\RatingsReviews\Plugin\Ui\OrderGridInvitation;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use PHPUnit\Framework\TestCase;

class OrderGridInvitationTest extends TestCase
{
    /** @var array */
    private $map = [];

    /**
     * @return void
     */
    public function testSharedColumnsAreQualifiedWithMainTable()
    {
        $this->runPlugin();

        $this->assertSame('main_table.created_at', $this->map['created_at']);
        $this->assertSame('main_table.updated_at', $this->map['updated_at']);
        $this->assertSame('main_table.store_id', $this->map['store_id']);
        $this->assertSame('main_table.status', $this->map['status']);
        $this->assertSame('lipscore_reminder.updated_at', $this->map[OrderGridInvitation::FIELD_DATE]);
        $this->assertArrayNotHasKey('order_id', $this->map);
        $this->assertArrayNotHasKey('increment_id', $this->map);
    }

    /**
     * @return void
     */
    public function testOtherGridsAreNotChanged()
    {
        $this->runPlugin('sales_invoice_grid_data_source');

        $this->assertSame([], $this->map);
    }

    /**
     * @param string $requestName
     * @return void
     */
    private function runPlugin($requestName = OrderGridInvitation::REQUEST_NAME)
    {
        $columns = [
            'sales_order_grid' => ['entity_id', 'status', 'store_id', 'increment_id', 'created_at', 'updated_at'],
            'lipscore_invitation' => [
                'order_id', 'store_id', 'status', 'source', 'attempts',
                'cron_attempts', 'message', 'created_at', 'updated_at',
            ],
        ];

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(true);
        $connection->method('describeTable')->willReturnCallback(function ($table) use ($columns) {
            return array_fill_keys($columns[$table], []);
        });

        $select = $this->createStub(Select::class);
        $select->method('getPart')->willReturn(['main_table' => ['tableName' => 'sales_order_grid']]);
        $select->method('joinLeft')->willReturnSelf();

        $collection = $this->createStub(AbstractDb::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);
        $collection->method('addFilterToMap')->willReturnCallback(function ($filter, $alias) use ($collection) {
            $this->map[$filter] = (string) $alias;
            return $collection;
        });

        $resource = $this->createStub(InvitationResource::class);
        $resource->method('getMainTable')->willReturn('lipscore_invitation');

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('log');

        $plugin = new OrderGridInvitation($resource, $logger);
        $plugin->afterGetReport($this->createStub(CollectionFactory::class), $collection, $requestName);
    }
}
