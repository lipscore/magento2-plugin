<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Source\Order;

use Lipscore\RatingsReviews\Model\Reminder\Status;
use Magento\Framework\Data\OptionSourceInterface;

class ReviewRequestStatus implements OptionSourceInterface
{
    /** @var Status */
    private $status;

    /**
     * @param Status $status
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->status->getLabels() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        $options[] = ['value' => Status::NONE, 'label' => $this->status->getLabel(Status::NONE)];

        return $options;
    }
}
