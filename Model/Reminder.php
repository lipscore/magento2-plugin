<?php

namespace Lipscore\RatingsReviews\Model;

use Lipscore\RatingsReviews\Model\Api\Request;
use Lipscore\RatingsReviews\Helper\Reminder as Helper;

class Reminder
{
    protected $helper;

    protected $config;

    protected $sender;

    public function __construct(
        Helper $helper,
        Request $sender,
        Config $config
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->sender = $sender;
    }

    public function send($order)
    {
        if (!$this->config->getApiKey($order->getStoreId())) {
            return false;
        }

        $data = $this->helper->data($order);

        return $this->sender->send($data, 'purchases', $order->getStoreId());
    }
}
