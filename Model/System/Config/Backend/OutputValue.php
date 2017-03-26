<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Backend;

class OutputValue extends \Magento\Framework\App\Config\Value
{
    protected $isChanged = false;

    protected static $cacheTypes = [
        \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER,
        \Magento\Framework\App\Cache\Type\Layout::TYPE_IDENTIFIER,
        \Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER
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
