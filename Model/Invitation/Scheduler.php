<?php

namespace Lipscore\RatingsReviews\Model\Invitation;

use Lipscore\RatingsReviews\Model\Api\Request;
use Lipscore\RatingsReviews\Model\Api\Response;
use Lipscore\RatingsReviews\Model\Api\ResponseClassifier;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation as InvitationResource;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Schedules invitations in Lipscore (POST /invitations) and records the result of every order.
 *
 * Orders are sent per store in batches of up to BATCH_SIZE. Lipscore creates the valid invitations of a batch and
 * returns only the created ones, so orders missing from the response are sent again one by one to get their
 * individual error. A request-level error (timeout, server or account error) stops the store: its remaining orders
 * are left untouched for a later run.
 *
 * Duplicates: if an invitation is created but recording the result fails, the order is sent again later and Lipscore
 * answers 409, which is recorded as already scheduled.
 */
class Scheduler
{
    public const API_PATH = 'invitations';

    /** Lipscore returns all created invitations synchronously for up to 20 invitations per request. */
    public const BATCH_SIZE = 20;

    /** Seconds; creating a batch takes longer than a single invitation. */
    public const BATCH_TIMEOUT = 30;

    /** @var Request */
    private $request;

    /** @var ResponseClassifier */
    private $classifier;

    /** @var PayloadBuilder */
    private $payloadBuilder;

    /** @var InvitationResource */
    private $invitationResource;

    /** @var Logger */
    private $errorLogger;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Request $request
     * @param ResponseClassifier $classifier
     * @param PayloadBuilder $payloadBuilder
     * @param InvitationResource $invitationResource
     * @param Logger $errorLogger
     * @param LoggerInterface $logger
     */
    public function __construct(
        Request $request,
        ResponseClassifier $classifier,
        PayloadBuilder $payloadBuilder,
        InvitationResource $invitationResource,
        Logger $errorLogger,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->classifier = $classifier;
        $this->payloadBuilder = $payloadBuilder;
        $this->invitationResource = $invitationResource;
        $this->errorLogger = $errorLogger;
        $this->logger = $logger;
    }

    /**
     * Schedule invitations for the orders and record the results.
     *
     * @param Order[] $orders
     * @param int $source One of the Source constants
     * @return ScheduleResult
     */
    public function schedule(array $orders, $source)
    {
        $result = new ScheduleResult();

        $ordersByStore = [];
        foreach ($orders as $order) {
            $ordersByStore[(int) $order->getStoreId()][] = $order;
        }

        foreach ($ordersByStore as $storeId => $storeOrders) {
            $this->scheduleStore($storeOrders, $storeId, (int) $source, $result);
        }

        return $result;
    }

    /**
     * Schedule one store's orders.
     *
     * @param Order[] $orders
     * @param int $storeId
     * @param int $source
     * @param ScheduleResult $result
     * @return void
     */
    private function scheduleStore(array $orders, $storeId, $source, ScheduleResult $result)
    {
        $payloads = $this->payloadBuilder->build($orders, $storeId);

        $records = [];
        $valid = [];
        foreach ($orders as $order) {
            $payload = $payloads[(int) $order->getId()] ?? null;
            if (is_array($payload)) {
                $valid[] = ['order' => $order, 'payload' => $payload];
                continue;
            }

            if ($payload instanceof \Throwable) {
                $this->errorLogger->log($payload);
            }
            $message = $payload instanceof \Throwable ? $payload->getMessage() : 'Invitation data could not be built.';
            $records[] = $this->record($order, Status::FAILED, $source, $message, true, $result);
        }
        $this->save($records);

        $chunks = array_chunk($valid, self::BATCH_SIZE);
        foreach ($chunks as $index => $chunk) {
            $stopped = count($chunk) === 1
                ? $this->sendSingle($chunk[0]['order'], $chunk[0]['payload'], $storeId, $source, $result)
                : $this->sendBatch($chunk, $storeId, $source, $result);

            if ($stopped) {
                foreach (array_slice($chunks, $index + 1) as $skipped) {
                    $result->addNotProcessed(count($skipped));
                }
                return;
            }
        }
    }

    /**
     * Send a batch; returns true when a request-level error stopped the store.
     *
     * @param array[] $items ['order' => Order, 'payload' => array]
     * @param int $storeId
     * @param int $source
     * @param ScheduleResult $result
     * @return bool
     */
    private function sendBatch(array $items, $storeId, $source, ScheduleResult $result)
    {
        $response = $this->post(['invitations' => array_column($items, 'payload')], $storeId, self::BATCH_TIMEOUT);
        $result->addRequest();
        $type = $response ? $this->classifier->classify($response) : null;

        if ($response && $this->classifier->isRequestLevel($type)) {
            $records = [];
            foreach ($items as $item) {
                $records[] = $this->recordFailure($item['order'], $type, $source, $response, $result);
            }
            $this->save($records);
            $this->stopStore($storeId, $type, $response, $result);
            return true;
        }

        $remaining = $items;
        if ($type === ResponseClassifier::SCHEDULED) {
            $created = $this->getCreatedOrderIncrementIds($response);
            $records = [];
            $remaining = [];
            foreach ($items as $item) {
                if (isset($created[(string) $item['order']->getIncrementId()])) {
                    $records[] = $this->record($item['order'], Status::SCHEDULED, $source, '', false, $result);
                } else {
                    $remaining[] = $item;
                }
            }
            $this->save($records);
        }

        // Rejected invitations, or a batch error (400, 409, 422) without per-invitation details:
        // send one by one to get each order's own result.
        foreach ($remaining as $index => $item) {
            if ($this->sendSingle($item['order'], $item['payload'], $storeId, $source, $result)) {
                $result->addNotProcessed(count($remaining) - $index - 1);
                return true;
            }
        }

        return false;
    }

    /**
     * Send one invitation; returns true when a request-level error stopped the store.
     *
     * @param Order $order
     * @param array $payload
     * @param int $storeId
     * @param int $source
     * @param ScheduleResult $result
     * @return bool
     */
    private function sendSingle(Order $order, array $payload, $storeId, $source, ScheduleResult $result)
    {
        $response = $this->post($payload, $storeId);
        $result->addRequest();
        if (!$response) {
            $this->save([
                $this->record($order, Status::FAILED, $source, 'Invitation data could not be sent.', true, $result),
            ]);
            return false;
        }

        $type = $this->classifier->classify($response);
        switch ($type) {
            case ResponseClassifier::SCHEDULED:
                $record = $this->record($order, Status::SCHEDULED, $source, '', false, $result);
                break;
            case ResponseClassifier::ALREADY_SCHEDULED:
                $message = $response->describe();
                $record = $this->record($order, Status::ALREADY_SCHEDULED, $source, $message, false, $result);
                break;
            case ResponseClassifier::REJECTED:
                $record = $this->record($order, Status::REJECTED, $source, $response->describe(), false, $result);
                break;
            default:
                $record = $this->recordFailure($order, $type, $source, $response, $result);
        }
        $this->save([$record]);

        if ($this->classifier->isRequestLevel($type)) {
            $this->stopStore($storeId, $type, $response, $result);
            return true;
        }

        return false;
    }

    /**
     * POST to the API; null when the data cannot be sent at all.
     *
     * @param array $data
     * @param int $storeId
     * @param int|null $timeout
     * @return Response|null
     */
    private function post(array $data, $storeId, $timeout = null)
    {
        try {
            return $this->request->post(self::API_PATH, $data, $storeId, $timeout);
        } catch (\Throwable $e) {
            $this->errorLogger->log($e);
            return null;
        }
    }

    /**
     * Increment IDs of the invitations in a 201 response, as keys.
     *
     * @param Response $response
     * @return array
     */
    private function getCreatedOrderIncrementIds(Response $response)
    {
        $data = $response->getData();
        $items = isset($data['invitation']) ? [$data] : ($data ?? []);

        $ids = [];
        foreach ($items as $item) {
            $invitation = isset($item['invitation']) && is_array($item['invitation']) ? $item['invitation'] : $item;
            if (is_array($invitation) && isset($invitation['internal_order_id'])) {
                $ids[(string) $invitation['internal_order_id']] = true;
            }
        }

        return $ids;
    }

    /**
     * Record of a request-level failure.
     *
     * Only temporary errors count towards the retry limit, account errors are retried once the store is no longer
     * paused.
     *
     * @param Order $order
     * @param string $type
     * @param int $source
     * @param Response $response
     * @param ScheduleResult $result
     * @return array
     */
    private function recordFailure(Order $order, $type, $source, Response $response, ScheduleResult $result)
    {
        return $this->record(
            $order,
            Status::FAILED,
            $source,
            $response->describe(),
            $type === ResponseClassifier::TEMPORARY,
            $result
        );
    }

    /**
     * Build a record and count it.
     *
     * @param Order $order
     * @param int $status
     * @param int $source
     * @param string $message
     * @param bool $countRetry Whether a failure counts towards the scheduled check's retry limit
     * @param ScheduleResult $result
     * @return array
     */
    private function record(Order $order, $status, $source, $message, $countRetry, ScheduleResult $result)
    {
        $result->addOrder($order->getId(), $status);

        return [
            'order_id' => (int) $order->getId(),
            'store_id' => (int) $order->getStoreId(),
            'status' => $status,
            'source' => $source,
            'message' => $message,
            'cron_attempt' => $countRetry && $source === Source::CRON,
        ];
    }

    /**
     * Save records.
     *
     * A failure is only logged: the order is sent again later and Lipscore answers 409, so marking it as failed
     * here would be wrong when the invitation was created.
     *
     * @param array[] $records
     * @return void
     */
    private function save(array $records)
    {
        if (!$records) {
            return;
        }

        try {
            $this->invitationResource->saveResults($records);
        } catch (\Throwable $e) {
            $this->errorLogger->log($e);
        }
    }

    /**
     * Record a request-level error for the store.
     *
     * @param int $storeId
     * @param string $type
     * @param Response $response
     * @param ScheduleResult $result
     * @return void
     */
    private function stopStore($storeId, $type, Response $response, ScheduleResult $result)
    {
        $message = $response->describe();
        $result->addStoreError($storeId, $type, $message);
        $this->logger->warning(sprintf(
            'Lipscore: invitations of store %d not scheduled (%s error): %s',
            $storeId,
            $type,
            $message
        ));
    }
}
