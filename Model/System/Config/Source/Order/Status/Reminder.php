<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order\Status;

use Lipscore\RatingsReviews\Model\System\Config\Source\Order\Status\AbstractStatus;
use Magento\Framework\Option\ArrayInterface;

class Reminder extends AbstractStatus implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' =>'', 'label' => __('Disable the Review Request Email')]
        ];

        try {
            $options = array_merge($options, $this->getStatusOptions());
        } catch (\Exception $e) {
            $this->logger->log($e);
        }

        return $options;
    }
}
