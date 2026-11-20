<?php

namespace Lipscore\RatingsReviews\Model\Invitation;

use Lipscore\RatingsReviews\Model\Api\ResponseClassifier;
use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation as InvitationResource;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Hourly scheduled check.
 *
 * Stores using the scheduled check: schedules invitations for orders that reached the configured status, also when
 * it was changed outside Magento. Stores using the real-time trigger: retries invitations that failed temporarily.
 */
class CronProcessor
{
    public const LOCK_NAME = 'lipscore_schedule_invitations';

    /**
     * Retry delay in seconds after the given number of failed scheduled check attempts; no retry after the last one.
     * The cron runs hourly, so delays are whole hours.
     */
    public const RETRY_DELAYS = [
        0 => 0,
        1 => 3600,
        2 => 10800,
        3 => 21600,
        4 => 86400,
    ];

    /** Seconds subtracted from retry delays, so a retry is not postponed by an hour because the cron ran early. */
    public const RETRY_TOLERANCE = 600;

    /** Stores in a row with a temporary API error before the run stops. */
    public const MAX_CONSECUTIVE_TEMPORARY_ERRORS = 3;

    /** Orders loaded at once. */
    private const LOAD_CHUNK_SIZE = 100;

    /** @var Config */
    private $config;

    /** @var Scheduler */
    private $scheduler;

    /** @var InvitationResource */
    private $invitationResource;

    /** @var OrderCollectionFactory */
    private $orderCollectionFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var LockManagerInterface */
    private $lockManager;

    /** @var StorePause */
    private $storePause;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $maxOrdersPerRun;

    /** @var int */
    private $consecutiveTemporaryErrors = 0;

    /**
     * @param Config $config
     * @param Scheduler $scheduler
     * @param InvitationResource $invitationResource
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LockManagerInterface $lockManager
     * @param StorePause $storePause
     * @param LoggerInterface $logger
     * @param int $maxOrdersPerRun
     */
    public function __construct(
        Config $config,
        Scheduler $scheduler,
        InvitationResource $invitationResource,
        OrderCollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager,
        LockManagerInterface $lockManager,
        StorePause $storePause,
        LoggerInterface $logger,
        $maxOrdersPerRun = 500
    ) {
        $this->config = $config;
        $this->scheduler = $scheduler;
        $this->invitationResource = $invitationResource;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->storeManager = $storeManager;
        $this->lockManager = $lockManager;
        $this->storePause = $storePause;
        $this->logger = $logger;
        $this->maxOrdersPerRun = max(1, (int) $maxOrdersPerRun);
    }

    /**
     * Cron entry point.
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->lockManager->lock(self::LOCK_NAME, 0)) {
            $this->logger->info('Lipscore: previous invitation check is still running, skipping.');
            return;
        }

        try {
            $this->consecutiveTemporaryErrors = 0;
            $remaining = $this->maxOrdersPerRun;
            foreach ($this->getActiveStores() as $storeId => $settings) {
                if ($remaining <= 0 || $this->isApiUnreachable()) {
                    break;
                }
                $remaining -= $this->processStore($storeId, $settings['status'], $settings['include_new'], $remaining);
            }

            if ($this->isApiUnreachable()) {
                $this->logger->warning('Lipscore: API unreachable, invitation check stopped until the next run.');
            }
        } finally {
            $this->lockManager->unlock(self::LOCK_NAME);
        }
    }

    /**
     * Stores to process.
     *
     * @return array [store_id => ['status' => order status, 'include_new' => bool]]
     */
    private function getActiveStores()
    {
        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            if (!$this->config->isLipscoreModuleEnabled($storeId) || !$this->config->getApiKey($storeId)) {
                continue;
            }

            $orderStatus = (string) $this->config->getEmailsOrderStatus($storeId);
            if ($orderStatus === '') {
                continue;
            }

            $pause = $this->storePause->getPause($storeId);
            if ($pause) {
                $this->logger->info(sprintf(
                    'Lipscore: store %d paused until %s after an account error: %s',
                    $storeId,
                    gmdate('Y-m-d H:i', (int) $pause['until']) . ' UTC',
                    $pause['message']
                ));
                continue;
            }

            $stores[$storeId] = [
                'status' => $orderStatus,
                // Real-time stores: new orders are sent on order save, only failures are retried here.
                'include_new' => $this->config->isEmailsCronTriggerEnabled($storeId),
            ];
        }

        return $stores;
    }

    /**
     * Schedule pending orders of one store; returns the number of orders processed.
     *
     * @param int $storeId
     * @param string $orderStatus
     * @param bool $includeNew
     * @param int $limit
     * @return int
     */
    private function processStore($storeId, $orderStatus, $includeNew, $limit)
    {
        $since = time() - $this->config->getEmailsCronLookbackDays($storeId) * 86400;
        $orderIds = $this->invitationResource->getPendingOrderIds(
            $storeId,
            $orderStatus,
            $this->formatUtc($since),
            $this->getRetryDueBefore(),
            $limit,
            $includeNew
        );
        if (!$orderIds) {
            return 0;
        }

        $counts = array_fill_keys([Status::SCHEDULED, Status::ALREADY_SCHEDULED, Status::REJECTED, Status::FAILED], 0);
        $notProcessed = 0;
        $chunks = array_chunk($orderIds, self::LOAD_CHUNK_SIZE);
        foreach ($chunks as $index => $chunk) {
            $result = $this->scheduler->schedule($this->loadOrders($chunk, $orderStatus), Source::CRON);
            foreach (array_keys($counts) as $status) {
                $counts[$status] += $result->getCount($status);
            }
            $notProcessed += $result->getNotProcessed();

            $error = $result->getStoreErrors()[$storeId] ?? null;
            if ($error) {
                $this->handleStoreError($storeId, $error);
                // All chunks before the last one are full.
                $notProcessed += max(0, count($orderIds) - ($index + 1) * self::LOAD_CHUNK_SIZE);
                break;
            }
            if ($result->getRequests()) {
                $this->consecutiveTemporaryErrors = 0;
                $this->storePause->resume($storeId);
            }
        }

        $this->logger->info(sprintf(
            'Lipscore: store %d, hourly check: %d scheduled, %d already scheduled, %d rejected, %d failed, '
            . '%d left for the next run.',
            $storeId,
            $counts[Status::SCHEDULED],
            $counts[Status::ALREADY_SCHEDULED],
            $counts[Status::REJECTED],
            $counts[Status::FAILED],
            $notProcessed
        ));

        return count($orderIds);
    }

    /**
     * Load orders that are still in the status; it may have changed since the query.
     *
     * @param int[] $orderIds
     * @param string $orderStatus
     * @return Order[]
     */
    private function loadOrders(array $orderIds, $orderStatus)
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['in' => $orderIds]);

        $orders = [];
        /** @var Order $order */
        foreach ($collection as $order) {
            if (strtolower((string) $order->getStatus()) === strtolower($orderStatus)) {
                $orders[] = $order;
            }
        }

        return $orders;
    }

    /**
     * React to a request-level error.
     *
     * @param int $storeId
     * @param array $error
     * @return void
     */
    private function handleStoreError($storeId, array $error)
    {
        if ($error['type'] === ResponseClassifier::ACCOUNT) {
            $this->storePause->pause($storeId, $error['message']);
            return;
        }

        $this->consecutiveTemporaryErrors++;
    }

    /**
     * Retry schedule as [failed attempts => latest failure time due for retry].
     *
     * @return string[]
     */
    private function getRetryDueBefore()
    {
        $now = time();
        $dueBefore = [];
        foreach (self::RETRY_DELAYS as $attempts => $delay) {
            $dueBefore[$attempts] = $this->formatUtc($now - max(0, $delay - self::RETRY_TOLERANCE));
        }

        return $dueBefore;
    }

    /**
     * Whether the run should stop.
     *
     * @return bool
     */
    private function isApiUnreachable()
    {
        return $this->consecutiveTemporaryErrors >= self::MAX_CONSECUTIVE_TEMPORARY_ERRORS;
    }

    /**
     * Magento stores timestamps in UTC.
     *
     * @param int $timestamp
     * @return string
     */
    private function formatUtc($timestamp)
    {
        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
