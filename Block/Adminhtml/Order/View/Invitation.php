<?php

namespace Lipscore\RatingsReviews\Block\Adminhtml\Order\View;

use Lipscore\RatingsReviews\Model\Logger;
use Lipscore\RatingsReviews\Model\Invitation\Source;
use Lipscore\RatingsReviews\Model\ResourceModel\Invitation as InvitationResource;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Lipscore invitation section of the admin order view.
 */
class Invitation extends Template
{
    /** @var string */
    protected $_template = 'Lipscore_RatingsReviews::order/view/invitation.phtml';

    /** @var InvitationResource */
    protected $invitationResource;

    /** @var Logger */
    protected $logger;

    /** @var Source */
    protected $source;

    /** @var array|null|false False until loaded */
    protected $record = false;

    /**
     * @param Context $context
     * @param InvitationResource $invitationResource
     * @param Logger $logger
     * @param Source $source
     * @param array $data
     */
    public function __construct(
        Context $context,
        InvitationResource $invitationResource,
        Logger $logger,
        Source $source,
        array $data = []
    ) {
        $this->invitationResource = $invitationResource;
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
            $this->record = $this->invitationResource->getRecord((int) $this->getRequest()->getParam('order_id'));
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
