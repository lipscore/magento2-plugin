<?php
namespace Lipscore\RatingsReviews\Helper;

class Image extends \Magento\Catalog\Helper\Image
{
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
