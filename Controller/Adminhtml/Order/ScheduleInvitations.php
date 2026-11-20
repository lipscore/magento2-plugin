<?php

namespace Lipscore\RatingsReviews\Controller\Adminhtml\Order;

use Lipscore\RatingsReviews\Model\Api\ResponseClassifier;
use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Invitation\Scheduler;
use Lipscore\RatingsReviews\Model\Invitation\Source;
use Lipscore\RatingsReviews\Model\Invitation\Status;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation as InvitationResource;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Bulk action of the sales order grid: schedules invitations for the selected orders regardless of their status,
 * skipping orders whose invitation is already scheduled.
 */
class ScheduleInvitations extends AbstractMassAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Lipscore_RatingsReviews::schedule_invitations';

    /** Orders are sent in batches of Scheduler::BATCH_SIZE, so this is up to 10 API requests. */
    public const MAX_ORDERS = 200;

    /** @var Config */
    protected $config;

    /** @var Scheduler */
    protected $scheduler;

    /** @var InvitationResource */
    protected $invitationResource;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param Scheduler $scheduler
     * @param InvitationResource $invitationResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Config $config,
        Scheduler $scheduler,
        InvitationResource $invitationResource,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->scheduler = $scheduler;
        $this->invitationResource = $invitationResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Schedule invitations.
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
                'Lipscore invitations can be scheduled for up to %1 orders at once, %2 were selected.',
                self::MAX_ORDERS,
                $total
            ));
        }

        $alreadyScheduled = array_flip($this->invitationResource->getScheduledOrderIds($collection->getAllIds()));
        $skippedScheduled = $notConfigured = 0;
        $orders = [];

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collection->getItems() as $order) {
            $storeId = (int) $order->getStoreId();
            if (isset($alreadyScheduled[(int) $order->getId()])) {
                $skippedScheduled++;
            } elseif (!$this->config->isLipscoreModuleEnabled($storeId) || !$this->config->getApiKey($storeId)) {
                $notConfigured++;
            } else {
                $orders[] = $order;
            }
        }

        $result = $this->scheduler->schedule($orders, Source::MANUAL);

        $this->addCountMessages($result, $skippedScheduled, $notConfigured);
        $this->addStoreErrorMessages($result->getStoreErrors(), $result->getNotProcessed());

        return $this->resultRedirectFactory->create()->setPath($this->getComponentRefererUrl());
    }

    /**
     * Add the per-status result messages.
     *
     * @param \Lipscore\RatingsReviews\Model\Invitation\ScheduleResult $result
     * @param int $skippedScheduled
     * @param int $notConfigured
     * @return void
     */
    protected function addCountMessages($result, $skippedScheduled, $notConfigured)
    {
        if ($count = $result->getCount(Status::SCHEDULED)) {
            $this->messageManager->addSuccessMessage(
                __('Lipscore invitations were scheduled for %1 order(s).', $count)
            );
        }
        if ($count = $result->getCount(Status::ALREADY_SCHEDULED)) {
            $this->messageManager->addNoticeMessage(__(
                'Lipscore reported that invitations for %1 order(s) were already scheduled, or the customer '
                . 'recently received an invitation. See the order view for details.',
                $count
            ));
        }
        if ($skippedScheduled) {
            $this->messageManager->addNoticeMessage(
                __('%1 order(s) were skipped, their invitations were already scheduled.', $skippedScheduled)
            );
        }
        if ($notConfigured) {
            $this->messageManager->addWarningMessage(__(
                '%1 order(s) were skipped, Lipscore is disabled or the API key is missing for their store view.',
                $notConfigured
            ));
        }
        if ($count = $result->getCount(Status::REJECTED)) {
            $this->messageManager->addErrorMessage(__(
                'Lipscore rejected the invitations for %1 order(s). See the order view for the reason, '
                . 'correct the order data and schedule them again.',
                $count
            ));
        }
        if ($count = $result->getCount(Status::FAILED)) {
            $this->messageManager->addErrorMessage(__(
                'Invitations could not be scheduled for %1 order(s). See the order view for details.',
                $count
            ));
        }
    }

    /**
     * Add messages for stores stopped by a request-level error.
     *
     * @param array[] $storeErrors
     * @param int $notProcessed
     * @return void
     */
    protected function addStoreErrorMessages(array $storeErrors, $notProcessed)
    {
        foreach ($storeErrors as $storeId => $error) {
            $storeName = $this->getStoreName($storeId);
            $this->messageManager->addErrorMessage($error['type'] === ResponseClassifier::ACCOUNT
                ? __(
                    'Lipscore did not accept the request for store view "%1". Check the API key, the secret and '
                    . 'your Lipscore account: %2',
                    $storeName,
                    $error['message']
                )
                : __(
                    'The Lipscore API is not available for store view "%1", please try again later: %2',
                    $storeName,
                    $error['message']
                ));
        }

        if ($notProcessed) {
            $this->messageManager->addErrorMessage(__(
                '%1 order(s) were not processed because of the error above.',
                $notProcessed
            ));
        }
    }

    /**
     * Store view name for messages.
     *
     * @param int $storeId
     * @return string
     */
    protected function getStoreName($storeId)
    {
        try {
            return (string) $this->storeManager->getStore($storeId)->getName();
        } catch (\Exception $e) {
            return (string) $storeId;
        }
    }
}
