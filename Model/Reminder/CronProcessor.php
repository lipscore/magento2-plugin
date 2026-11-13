<?php

namespace Lipscore\RatingsReviews\Model\Reminder;

use Lipscore\RatingsReviews\Model\Api\ConnectionError;
use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\ResourceModel\OrderReminder as OrderReminderResource;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends review requests for orders that reached the configured status, also when it was changed outside Magento.
 */
class CronProcessor
{
    public const LOCK_NAME = 'lipscore_send_review_requests';

    /** Retry delay in seconds, keyed by failed cron attempts; no retry after the last one. */
    public const RETRY_DELAYS = [
        0 => 0,
        1 => 1800,
        2 => 7200,
        3 => 21600,
        4 => 86400,
    ];

    public const MAX_CONSECUTIVE_CONNECTION_ERRORS = 3;

    private const RESULT_SENT = 1;
    private const RESULT_FAILED = 2;
    private const RESULT_CONNECTION_ERROR = 3;
    private const RESULT_SKIPPED = 4;

    /** @var Config */
    protected $config;

    /** @var OrderSender */
    protected $orderSender;

    /** @var OrderReminderResource */
    protected $orderReminderResource;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var LockManagerInterface */
    protected $lockManager;

    /** @var Logger */
    protected $errorLogger;

    /** @var LoggerInterface */
    protected $logger;

    /** @var int */
    protected $batchSize;

    /** @var ConnectionError */
    protected $connectionError;

    /** @var int */
    protected $consecutiveConnectionErrors = 0;

    /**
     * @param Config $config
     * @param OrderSender $orderSender
     * @param OrderReminderResource $orderReminderResource
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param LockManagerInterface $lockManager
     * @param Logger $errorLogger
     * @param LoggerInterface $logger
     * @param ConnectionError $connectionError
     * @param int $batchSize
     */
    public function __construct(
        Config $config,
        OrderSender $orderSender,
        OrderReminderResource $orderReminderResource,
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        LockManagerInterface $lockManager,
        Logger $errorLogger,
        LoggerInterface $logger,
        ConnectionError $connectionError,
        $batchSize = 100
    ) {
        $this->connectionError = $connectionError;
        $this->config = $config;
        $this->orderSender = $orderSender;
        $this->orderReminderResource = $orderReminderResource;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->lockManager = $lockManager;
        $this->errorLogger = $errorLogger;
        $this->logger = $logger;
        $this->batchSize = max(1, (int) $batchSize);
    }

    /**
     * Cron entry point.
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->lockManager->lock(self::LOCK_NAME, 0)) {
            $this->logger->info('Lipscore: previous review request check is still running, skipping.');
            return;
        }

        try {
            $this->consecutiveConnectionErrors = 0;
            $remaining = $this->batchSize;
            foreach ($this->getActiveStores() as $storeId => $orderStatus) {
                if ($remaining <= 0 || $this->isApiUnreachable()) {
                    break;
                }
                $remaining -= $this->processStore($storeId, $orderStatus, $remaining);
            }

            if ($this->isApiUnreachable()) {
                $this->logger->warning('Lipscore: API unreachable, review request check stopped until the next run.');
            }
        } finally {
            $this->lockManager->unlock(self::LOCK_NAME);
        }
    }

    /**
     * Stores using the scheduled check.
     *
     * @return array [store_id => order status]
     */
    protected function getActiveStores()
    {
        $stores = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            $orderStatus = $this->getOrderStatus($storeId);
            if ($orderStatus !== null) {
                $stores[$storeId] = $orderStatus;
            }
        }

        return $stores;
    }

    /**
     * Configured order status, or null when the store is not processed.
     *
     * @param int $storeId
     * @return string|null
     */
    protected function getOrderStatus($storeId)
    {
        if (!$this->config->isEmailsCronTriggerEnabled($storeId)
            || !$this->config->isLipscoreModuleEnabled($storeId)
            || !$this->config->getApiKey($storeId)
        ) {
            return null;
        }

        $orderStatus = (string) $this->config->getEmailsOrderStatus($storeId);

        return $orderStatus !== '' ? $orderStatus : null;
    }

    /**
     * Sends pending orders of one store.
     *
     * @param int $storeId
     * @param string $orderStatus
     * @param int $limit
     * @return int
     */
    protected function processStore($storeId, $orderStatus, $limit)
    {
        $since = time() - $this->config->getEmailsCronLookbackDays($storeId) * 86400;

        $orderIds = $this->orderReminderResource->getPendingOrderIds(
            $storeId,
            $orderStatus,
            $this->formatUtc($since),
            $this->getRetryDueBefore(),
            $limit
        );

        $sent = $processed = 0;
        foreach ($orderIds as $orderId) {
            if ($this->isApiUnreachable()) {
                break;
            }
            $processed++;

            $result = $this->processOrder($orderId, $storeId, $orderStatus);
            if ($result === self::RESULT_CONNECTION_ERROR) {
                $this->consecutiveConnectionErrors++;
                continue;
            }
            if ($result !== self::RESULT_SKIPPED) {
                $this->consecutiveConnectionErrors = 0;
            }
            if ($result === self::RESULT_SENT) {
                $sent++;
            }
        }

        if ($processed) {
            $this->logger->info(sprintf(
                'Lipscore: store %d, %d of %d review requests sent by scheduled check.',
                $storeId,
                $sent,
                $processed
            ));
        }

        return $processed;
    }

    /**
     * Retry schedule as [failed attempts => latest failure time due for retry].
     *
     * @return string[]
     */
    protected function getRetryDueBefore()
    {
        $now = time();
        $dueBefore = [];
        foreach (self::RETRY_DELAYS as $attempts => $delay) {
            $dueBefore[$attempts] = $this->formatUtc($now - $delay);
        }

        return $dueBefore;
    }

    /**
     * Whether the run should stop.
     *
     * @return bool
     */
    protected function isApiUnreachable()
    {
        return $this->consecutiveConnectionErrors >= self::MAX_CONSECUTIVE_CONNECTION_ERRORS;
    }

    /**
     * Sends one order and records the outcome.
     *
     * @param int $orderId
     * @param int $storeId
     * @param string $orderStatus
     * @return int
     */
    protected function processOrder($orderId, $storeId, $orderStatus)
    {
        try {
            $order = $this->orderRepository->get($orderId);

            // Status may have changed since the query.
            if (strtolower((string) $order->getStatus()) !== strtolower($orderStatus)) {
                return self::RESULT_SKIPPED;
            }

            $result = $this->orderSender->send($order);
            if ($result === false) {
                $this->orderReminderResource->markFailed(
                    $orderId,
                    $storeId,
                    Source::CRON,
                    'Lipscore API request was not successful.'
                );
                return self::RESULT_FAILED;
            }

            $this->orderReminderResource->markSent($orderId, $storeId, Source::CRON);
            return self::RESULT_SENT;
        } catch (\Throwable $e) {
            $this->errorLogger->log($e);
            try {
                $this->orderReminderResource->markFailed(
                    $orderId,
                    $storeId,
                    Source::CRON,
                    $e->getMessage()
                );
            } catch (\Throwable $logError) {
                $this->errorLogger->log($logError);
            }
            return $this->connectionError->matches($e) ? self::RESULT_CONNECTION_ERROR : self::RESULT_FAILED;
        }
    }

    /**
     * Magento stores timestamps in UTC.
     *
     * @param int $timestamp
     * @return string
     */
    protected function formatUtc($timestamp)
    {
        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
