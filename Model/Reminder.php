<?php

namespace Lipscore\RatingsReviews\Model;

use Lipscore\RatingsReviews\Model\Api\Request;

class Reminder
{
    /**
     * @var \Lipscore\RatingsReviews\Helper\Reminder
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Request
     */
    protected $sender;

    /**
     * Constructor.
     *
     * @param \Lipscore\RatingsReviews\Helper\Reminder $helper
     * @param Request $sender
     * @param Config $config
     */
    public function __construct(
        \Lipscore\RatingsReviews\Helper\Reminder $helper,
        Request $sender,
        Config $config
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->sender = $sender;
    }

    /**
     * Schedule an invitation for the given order, without recording it.
     *
     * @deprecated 2.3.0 Does not tell temporary errors from permanent ones and does not record the result.
     * @see \Lipscore\RatingsReviews\Model\Invitation\Scheduler::schedule()
     * @param \Magento\Sales\Model\Order $order
     * @return array|false Decoded response, false on failure
     */
    public function send($order)
    {
        if (!$this->config->getApiKey($order->getStoreId())) {
            return false;
        }

        $data = $this->helper->invitationData($order);
        $this->sender->setStoreId($order->getStoreId());

        return $this->sender->send($data, \Lipscore\RatingsReviews\Model\Invitation\Scheduler::API_PATH);
    }
}
