<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Backend;

use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\Cache\Type\Layout;
use Magento\Framework\App\Config\Value;
use Magento\PageCache\Model\Cache\Type;

class OutputValue extends Value
{
    protected $isChanged = false;

    protected static $cacheTypes = [
        Block::TYPE_IDENTIFIER,
        Layout::TYPE_IDENTIFIER,
        Type::TYPE_IDENTIFIER
    ];

    public function afterSave()
    {
        if ($this->isValueChanged()) {
            foreach (static::$cacheTypes as $type) {
                $this->cacheTypeList->invalidate($type);
            }
        }

        return parent::afterSave();
    }
}
