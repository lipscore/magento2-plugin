<?php

namespace Lipscore\RatingsReviews\Model\Invitation;

/**
 * Outcome of Scheduler::schedule(): order counts per status and the stores that could not be processed.
 */
class ScheduleResult
{
    /** @var int[] [Status => order count] */
    private $counts = [];

    /** @var array[] [store ID => ['type' => ResponseClassifier constant, 'message' => string]] */
    private $storeErrors = [];

    /** @var int */
    private $notProcessed = 0;

    /** @var int */
    private $requests = 0;

    /** @var int[] [order ID => Status] */
    private $orderStatuses = [];

    /**
     * Record an order's status.
     *
     * @param int $orderId
     * @param int $status
     * @return void
     */
    public function addOrder($orderId, $status)
    {
        $this->orderStatuses[(int) $orderId] = (int) $status;
        $this->counts[(int) $status] = ($this->counts[(int) $status] ?? 0) + 1;
    }

    /**
     * Number of orders with the status.
     *
     * @param int $status
     * @return int
     */
    public function getCount($status)
    {
        return $this->counts[(int) $status] ?? 0;
    }

    /**
     * Status of an order, null when it was not sent.
     *
     * @param int $orderId
     * @return int|null
     */
    public function getOrderStatus($orderId)
    {
        return $this->orderStatuses[(int) $orderId] ?? null;
    }

    /**
     * Record a request-level error that stopped the store.
     *
     * @param int $storeId
     * @param string $type ResponseClassifier::TEMPORARY or ResponseClassifier::ACCOUNT
     * @param string $message
     * @return void
     */
    public function addStoreError($storeId, $type, $message)
    {
        $this->storeErrors[(int) $storeId] = ['type' => $type, 'message' => (string) $message];
    }

    /**
     * Request-level errors.
     *
     * @return array[] [store ID => ['type' => string, 'message' => string]]
     */
    public function getStoreErrors()
    {
        return $this->storeErrors;
    }

    /**
     * Count orders not sent because their store was stopped.
     *
     * @param int $count
     * @return void
     */
    public function addNotProcessed($count)
    {
        $this->notProcessed += (int) $count;
    }

    /**
     * Orders not sent because their store was stopped; they keep their previous status.
     *
     * @return int
     */
    public function getNotProcessed()
    {
        return $this->notProcessed;
    }

    /**
     * Count an API request.
     *
     * @return void
     */
    public function addRequest()
    {
        $this->requests++;
    }

    /**
     * Number of API requests made.
     *
     * @return int
     */
    public function getRequests()
    {
        return $this->requests;
    }
}
