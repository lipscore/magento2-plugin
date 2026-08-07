<?php

namespace Lipscore\RatingsReviews\Model\System\Config\Backend;

use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\Cache\Type\Layout;
use Magento\Framework\App\Config\Value;
use Magento\PageCache\Model\Cache\Type;

class OutputValue extends Value
{
    /**
     * @var bool
     */
    protected $isChanged = false;

    /**
     * @var array
     */
    protected static $cacheTypes = [
        Block::TYPE_IDENTIFIER,
        Layout::TYPE_IDENTIFIER,
        Type::TYPE_IDENTIFIER
    ];

    /**
     * Trim the value before saving.
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->setValue(trim((string) $this->getValue()));

        return parent::beforeSave();
    }

    /**
     * Invalidate relevant cache types after saving, if the value changed.
     *
     * @return $this
     */
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
