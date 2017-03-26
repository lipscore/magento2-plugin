<?php

namespace Lipscore\RatingsReviews\Model;

class Reminder
{
    protected $dataHelper;
    protected $config;
    protected $senderFactory;

    public function __construct(
        \Lipscore\RatingsReviews\Helper\ReminderFactory $reminderHelperFactory,
        \Lipscore\RatingsReviews\Model\Api\RequestFactory $senderFactory,
        $config
    ){
        $this->config = $config;
        $this->dataHelper = $reminderHelperFactory->create([
            'config' => $this->config
        ]);
        $this->senderFactory = $senderFactory;
    }

    public function send($order)
    {
        if (!$this->config->isValidApiKey()) {
            return false;
        }

        $data = $this->dataHelper->data($order);
        return $this->sender()->send($data);
    }

    protected function sender()
    {
        return $this->senderFactory->create([
            'config' => $this->config,
            'path'   => 'purchases',
            'params' => [
                'timeout' => $this->config->reminderTimeout()
            ]
        ]);
    }
}
