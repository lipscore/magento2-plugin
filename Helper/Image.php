<?php

namespace Lipscore\RatingsReviews\Helper;

use Magento\Catalog\Helper\Image as BaseImage;

class Image extends BaseImage
{
    /**
     * Initialize the base image file if it is not already set.
     *
     * @return $this
     */
    protected function initBaseFile()
    {
        $model = $this->_getModel();
        $baseFile = $model->getBaseFile();
        if (!$baseFile) {
            if ($this->getImageFile()) {
                $model->setBaseFile($this->getImageFile());
            } else {
                $model->setBaseFile($this->getProduct()->getImage());
            }
        }

        return $this;
    }
}
