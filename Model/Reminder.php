<?php

namespace Lipscore\RatingsReviews\Model;

use Lipscore\RatingsReviews\Model\Api\Request;

class Reminder
{
    protected $helper;

    protected $config;

    protected $sender;

    public function __construct(
        \Lipscore\RatingsReviews\Helper\Reminder $helper,
        Request $sender,
        Config $config
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->sender = $sender;
    }

    public function send($order)
    {
        if (!$this->config->getApiKey()) {
            return false;
        }

        $data = $this->helper->data($order);

        return $this->sender->send($data);
    }
}
