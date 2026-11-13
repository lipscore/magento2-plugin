<?php

namespace Lipscore\RatingsReviews\Controller\Adminhtml\Order;

use Lipscore\RatingsReviews\Model\Api\ConnectionError;
use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Reminder\OrderSender;
use Lipscore\RatingsReviews\Model\Reminder\Source;
use Lipscore\RatingsReviews\Model\ResourceModel\OrderReminder as OrderReminderResource;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Sends review requests for selected orders regardless of status, skipping already sent ones.
 */
class MassSendReviewRequest extends AbstractMassAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Lipscore_RatingsReviews::send_review_request';

    /** Each order is a separate API request. */
    public const MAX_ORDERS = 200;

    public const MAX_CONSECUTIVE_CONNECTION_ERRORS = 3;

    /** @var Config */
    protected $config;

    /** @var OrderSender */
    protected $orderSender;

    /** @var OrderReminderResource */
    protected $orderReminderResource;

    /** @var Logger */
    protected $errorLogger;

    /** @var ConnectionError */
    protected $connectionError;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param OrderSender $orderSender
     * @param OrderReminderResource $orderReminderResource
     * @param Logger $errorLogger
     * @param ConnectionError $connectionError
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Config $config,
        OrderSender $orderSender,
        OrderReminderResource $orderReminderResource,
        Logger $errorLogger,
        ConnectionError $connectionError
    ) {
        $this->connectionError = $connectionError;
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->orderSender = $orderSender;
        $this->orderReminderResource = $orderReminderResource;
        $this->errorLogger = $errorLogger;
    }

    /**
     * Send review requests.
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws LocalizedException
     */
    protected function massAction(AbstractCollection $collection)
    {
        $total = $collection->getSize();
        if ($total > self::MAX_ORDERS) {
            throw new LocalizedException(__(
                'Lipscore review requests can be sent for up to %1 orders at once, %2 were selected.',
                self::MAX_ORDERS,
                $total
            ));
        }

        $alreadySent = array_flip($this->orderReminderResource->getSentOrderIds($collection->getAllIds()));
        $sent = $skippedSent = $notConfigured = $failed = $processed = $connectionErrors = 0;

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collection->getItems() as $order) {
            if ($connectionErrors >= self::MAX_CONSECUTIVE_CONNECTION_ERRORS) {
                break;
            }
            $processed++;

            $orderId = (int) $order->getId();
            $storeId = (int) $order->getStoreId();

            if (isset($alreadySent[$orderId])) {
                $skippedSent++;
                continue;
            }

            if (!$this->config->isLipscoreModuleEnabled($storeId) || !$this->config->getApiKey($storeId)) {
                $notConfigured++;
                continue;
            }

            try {
                $result = $this->orderSender->send($order);
            } catch (\Throwable $e) {
                $this->errorLogger->log($e);
                $failed++;
                $connectionErrors = $this->connectionError->matches($e) ? $connectionErrors + 1 : 0;
                $this->markFailed($orderId, $storeId, $e->getMessage());
                continue;
            }

            $connectionErrors = 0;
            if ($result === false) {
                $failed++;
                $this->markFailed($orderId, $storeId, 'Lipscore API request was not successful.');
                continue;
            }
            $sent++;

            try {
                $this->orderReminderResource->markSent($orderId, $storeId, Source::MANUAL);
            } catch (\Throwable $e) {
                $this->errorLogger->log($e);
            }
        }

        $this->addMessages($sent, $skippedSent, $notConfigured, $failed, $total - $processed);

        return $this->resultRedirectFactory->create()->setPath($this->getComponentRefererUrl());
    }

    /**
     * Record a failed request.
     *
     * @param int $orderId
     * @param int $storeId
     * @param string $error
     * @return void
     */
    protected function markFailed($orderId, $storeId, $error)
    {
        try {
            $this->orderReminderResource->markFailed($orderId, $storeId, Source::MANUAL, $error);
        } catch (\Throwable $e) {
            $this->errorLogger->log($e);
        }
    }

    /**
     * Add result messages.
     *
     * @param int $sent
     * @param int $skippedSent
     * @param int $notConfigured
     * @param int $failed
     * @param int $notProcessed
     * @return void
     */
    protected function addMessages($sent, $skippedSent, $notConfigured, $failed, $notProcessed = 0)
    {
        if ($sent) {
            $this->messageManager->addSuccessMessage(
                __('Lipscore review requests were sent for %1 order(s).', $sent)
            );
        }
        if ($skippedSent) {
            $this->messageManager->addNoticeMessage(
                __('%1 order(s) were skipped, review requests were already sent for them.', $skippedSent)
            );
        }
        if ($notConfigured) {
            $this->messageManager->addWarningMessage(__(
                '%1 order(s) were skipped, Lipscore is disabled or the API key is missing for their store view.',
                $notConfigured
            ));
        }
        if ($failed) {
            $this->messageManager->addErrorMessage(__(
                'Review requests could not be sent for %1 order(s). See var/log/lipscore_exception.log for details.',
                $failed
            ));
        }
        if ($notProcessed) {
            $this->messageManager->addErrorMessage(__(
                'The Lipscore API could not be reached, %1 order(s) were not processed. Please try again later.',
                $notProcessed
            ));
        }
    }
}
