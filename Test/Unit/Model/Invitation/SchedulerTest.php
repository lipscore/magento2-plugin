<?php

namespace Lipscore\RatingsReviews\Test\Unit\Model\Invitation;

use Lipscore\RatingsReviews\Model\Api\Request;
use Lipscore\RatingsReviews\Model\Api\Response;
use Lipscore\RatingsReviews\Model\Api\ResponseClassifier;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Invitation\PayloadBuilder;
use Lipscore\RatingsReviews\Model\Invitation\Scheduler;
use Lipscore\RatingsReviews\Model\Invitation\Source;
use Lipscore\RatingsReviews\Model\Invitation\Status;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SchedulerTest extends TestCase
{
    /** @var Request&Stub */
    private $request;

    /** @var PayloadBuilder&Stub */
    private $payloadBuilder;

    /** @var Invitation&Stub */
    private $resource;

    /** @var Logger&Stub */
    private $errorLogger;

    /** @var Scheduler */
    private $scheduler;

    /** @var array[] Saved records */
    private $saved = [];

    /** @var array[] Sent requests as [data, timeout] */
    private $sent = [];

    /** @var callable Returns the Response for the sent data */
    private $api;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->createStub(Request::class);
        $this->payloadBuilder = $this->createStub(PayloadBuilder::class);
        $this->resource = $this->createStub(Invitation::class);
        $this->errorLogger = $this->createStub(Logger::class);

        $this->payloadBuilder->method('build')->willReturnCallback(function (array $orders) {
            $payloads = [];
            foreach ($orders as $order) {
                $payloads[$order->getId()] = [
                    'invitation' => ['internal_order_id' => $order->getIncrementId()],
                    'products' => [],
                ];
            }
            return $payloads;
        });
        $this->resource->method('saveResults')->willReturnCallback(function (array $records) {
            foreach ($records as $record) {
                $this->saved[$record['order_id']] = $record;
            }
        });
        $this->request->method('post')->willReturnCallback(function ($path, array $data, $storeId, $timeout) {
            $this->assertSame('invitations', $path);
            $this->sent[] = [$data, $timeout];
            return ($this->api)($data);
        });

        $this->scheduler = new Scheduler(
            $this->request,
            new ResponseClassifier(),
            $this->payloadBuilder,
            $this->resource,
            $this->errorLogger,
            $this->createStub(LoggerInterface::class)
        );
    }

    /**
     * @return void
     */
    public function testSingleOrderUsesSingleInvitationFormat()
    {
        $this->api = function () {
            return new Response(201, '{"invitation":{"id":1,"internal_order_id":"100001"}}');
        };

        $result = $this->scheduler->schedule($this->orders(1), Source::EVENT);

        $this->assertSame(Status::SCHEDULED, $result->getOrderStatus(1));
        $this->assertCount(1, $this->sent);
        $this->assertArrayHasKey('invitation', $this->sent[0][0]);
        $this->assertNull($this->sent[0][1], 'Single requests use the default timeout.');
        $this->assertSame(Status::SCHEDULED, $this->saved[1]['status']);
        $this->assertSame(Source::EVENT, $this->saved[1]['source']);
    }

    /**
     * Lipscore creates the valid invitations of a batch and returns only those; the others are sent one by one.
     *
     * @return void
     */
    public function testBatchesOfTwentyAndMissingInvitationsSentOneByOne()
    {
        $this->api = function (array $data) {
            if (isset($data['invitations'])) {
                $created = [];
                foreach ($data['invitations'] as $invitation) {
                    $id = $invitation['invitation']['internal_order_id'];
                    if (!in_array($id, ['100003', '100004'], true)) {
                        $created[] = ['invitation' => ['internal_order_id' => $id]];
                    }
                }
                return new Response(201, json_encode($created));
            }

            return $data['invitation']['internal_order_id'] === '100003'
                ? new Response(409, '{"status":"conflict","message":"Customer has this purchase already"}')
                : new Response(400, '{"status":"error","message":"Buyer cannot be found/created"}');
        };

        $result = $this->scheduler->schedule($this->orders(25), Source::CRON);

        // 20 + 5 in batches, then orders 3 and 4 one by one.
        $this->assertCount(4, $this->sent);
        $this->assertCount(20, $this->sent[0][0]['invitations']);
        $this->assertSame(Scheduler::BATCH_TIMEOUT, $this->sent[0][1]);
        $this->assertCount(5, $this->sent[3][0]['invitations']);
        $this->assertSame(23, $result->getCount(Status::SCHEDULED));
        $this->assertSame(Status::ALREADY_SCHEDULED, $result->getOrderStatus(3));
        $this->assertSame('HTTP 409: Customer has this purchase already', $this->saved[3]['message']);
        $this->assertSame(Status::REJECTED, $result->getOrderStatus(4));
        $this->assertFalse($this->saved[4]['cron_attempt'], 'Rejected invitations are not retried.');
        $this->assertSame([], $result->getStoreErrors());
    }

    /**
     * A batch error without per-invitation details: each order is sent alone to get its own result.
     *
     * @return void
     */
    public function testBatchValidationErrorFallsBackToSingleRequests()
    {
        $this->api = function (array $data) {
            return isset($data['invitations'])
                ? new Response(400, '{"status":"error","message":"Validation failed"}')
                : new Response(201, '{}');
        };

        $result = $this->scheduler->schedule($this->orders(3), Source::MANUAL);

        $this->assertCount(4, $this->sent);
        $this->assertSame(3, $result->getCount(Status::SCHEDULED));
    }

    /**
     * @return void
     */
    public function testTemporaryErrorStopsStoreAndCountsRetry()
    {
        $this->api = function () {
            return new Response(503, '503 Service Unavailable');
        };

        $result = $this->scheduler->schedule($this->orders(25), Source::CRON);

        $this->assertCount(1, $this->sent);
        $this->assertSame(20, $result->getCount(Status::FAILED));
        $this->assertSame(5, $result->getNotProcessed());
        $this->assertTrue($this->saved[1]['cron_attempt']);
        $this->assertArrayNotHasKey(21, $this->saved, 'Orders not sent keep their previous record.');
        $this->assertSame(ResponseClassifier::TEMPORARY, $result->getStoreErrors()[1]['type']);
    }

    /**
     * @return void
     */
    public function testAccountErrorDoesNotCountRetry()
    {
        $this->api = function () {
            return new Response(401, '');
        };

        $result = $this->scheduler->schedule($this->orders(1), Source::CRON);

        $this->assertSame(Status::FAILED, $result->getOrderStatus(1));
        $this->assertFalse($this->saved[1]['cron_attempt']);
        $this->assertSame('HTTP 401', $this->saved[1]['message']);
        $this->assertSame(ResponseClassifier::ACCOUNT, $result->getStoreErrors()[1]['type']);
    }

    /**
     * A failure while sending the missing invitations of a batch one by one stops the store.
     *
     * @return void
     */
    public function testErrorDuringSingleRetriesStopsStore()
    {
        $this->api = function (array $data) {
            return isset($data['invitations']) ? new Response(201, '[]') : new Response(0, '');
        };

        $result = $this->scheduler->schedule($this->orders(3), Source::CRON);

        $this->assertCount(2, $this->sent);
        $this->assertSame(Status::FAILED, $result->getOrderStatus(1));
        $this->assertSame(2, $result->getNotProcessed());
    }

    /**
     * The invitation was created, recording it failed: only logged, the order is sent again later and Lipscore
     * answers 409.
     *
     * @return void
     */
    public function testRecordingFailureIsNotTreatedAsApiFailure()
    {
        $resource = $this->createStub(Invitation::class);
        $resource->method('saveResults')->willThrowException(new \RuntimeException('Deadlock'));
        $errorLogger = $this->createMock(Logger::class);
        $errorLogger->expects($this->once())->method('log');
        $this->api = function () {
            return new Response(201, '{}');
        };
        $scheduler = new Scheduler(
            $this->request,
            new ResponseClassifier(),
            $this->payloadBuilder,
            $resource,
            $errorLogger,
            $this->createStub(LoggerInterface::class)
        );

        $result = $scheduler->schedule($this->orders(1), Source::CRON);

        $this->assertSame(Status::SCHEDULED, $result->getOrderStatus(1));
    }

    /**
     * @return void
     */
    public function testPayloadErrorIsRecordedAsFailure()
    {
        $payloadBuilder = $this->createStub(PayloadBuilder::class);
        $payloadBuilder->method('build')->willReturn([1 => new \RuntimeException('Product not found')]);
        $scheduler = new Scheduler(
            $this->request,
            new ResponseClassifier(),
            $payloadBuilder,
            $this->resource,
            $this->errorLogger,
            $this->createStub(LoggerInterface::class)
        );

        $result = $scheduler->schedule($this->orders(1), Source::CRON);

        $this->assertSame([], $this->sent);
        $this->assertSame(Status::FAILED, $result->getOrderStatus(1));
        $this->assertSame('Product not found', $this->saved[1]['message']);
        $this->assertTrue($this->saved[1]['cron_attempt']);
    }

    /**
     * @return void
     */
    public function testOrdersAreGroupedByStore()
    {
        $this->api = function (array $data) {
            return new Response(201, json_encode(array_map(function ($invitation) {
                return ['invitation' => $invitation['invitation']];
            }, $data['invitations'])));
        };
        $orders = array_merge($this->orders(2, 1), $this->orders(2, 2, 10));

        $result = $this->scheduler->schedule($orders, Source::MANUAL);

        $this->assertCount(2, $this->sent);
        $this->assertSame(4, $result->getCount(Status::SCHEDULED));
        $this->assertSame(2, $this->saved[11]['store_id']);
    }

    /**
     * Order mocks with IDs from $firstId + 1 and increment IDs 100000 + ID.
     *
     * @param int $count
     * @param int $storeId
     * @param int $firstId
     * @return Order[]
     */
    private function orders($count, $storeId = 1, $firstId = 0)
    {
        $orders = [];
        for ($id = $firstId + 1; $id <= $firstId + $count; $id++) {
            $order = $this->createStub(Order::class);
            $order->method('getId')->willReturn($id);
            $order->method('getStoreId')->willReturn($storeId);
            $order->method('getIncrementId')->willReturn((string) (100000 + $id));
            $orders[] = $order;
        }

        return $orders;
    }
}
