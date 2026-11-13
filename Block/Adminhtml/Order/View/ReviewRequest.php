<?php

namespace Lipscore\RatingsReviews\Block\Adminhtml\Order\View;

use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Reminder\Source;
use Lipscore\RatingsReviews\Model\ResourceModel\OrderReminder as OrderReminderResource;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Lipscore review request section of the admin order view.
 */
class ReviewRequest extends Template
{
    /** @var string */
    protected $_template = 'Lipscore_RatingsReviews::order/view/review_request.phtml';

    /** @var OrderReminderResource */
    protected $orderReminderResource;

    /** @var Logger */
    protected $logger;

    /** @var Source */
    protected $source;

    /** @var array|null|false False until loaded */
    protected $record = false;

    /**
     * @param Context $context
     * @param OrderReminderResource $orderReminderResource
     * @param Logger $logger
     * @param Source $source
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderReminderResource $orderReminderResource,
        Logger $logger,
        Source $source,
        array $data = []
    ) {
        $this->orderReminderResource = $orderReminderResource;
        $this->logger = $logger;
        $this->source = $source;
        parent::__construct($context, $data);
    }

    /**
     * Get the viewed order's record.
     *
     * @return array|null
     */
    public function getRecord()
    {
        if ($this->record === false) {
            $this->record = $this->orderReminderResource->getRecord((int) $this->getRequest()->getParam('order_id'));
        }

        return $this->record;
    }

    /**
     * Get source label.
     *
     * @param int|string $source
     * @return \Magento\Framework\Phrase
     */
    public function getSourceLabel($source)
    {
        return $this->source->getLabel($source);
    }

    /**
     * Render nothing before setup:upgrade creates the table.
     *
     * @return string
     */
    protected function _toHtml()
    {
        try {
            $this->getRecord();
        } catch (\Exception $e) {
            $this->logger->log($e);
            return '';
        }

        return parent::_toHtml();
    }
}
