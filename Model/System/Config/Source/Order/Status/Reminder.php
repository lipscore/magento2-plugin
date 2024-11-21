<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order\Status;

use Magento\Framework\Data\OptionSourceInterface;

class Reminder extends AbstractStatus implements OptionSourceInterface
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
