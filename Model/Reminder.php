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
     * Send a review reminder for the given order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function send($order)
    {
        if (!$this->config->getApiKey($order->getStoreId())) {
            return false;
        }

        $data = $this->helper->data($order);
        $this->sender->setStoreId($order->getStoreId());

        return $this->sender->send($data);
    }
}
