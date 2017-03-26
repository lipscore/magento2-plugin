<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Observer\AbstractObserver;

class Output extends AbstractObserver
{
    protected static $logFile = 'ls_output_observer';

    const MAGENTO_REVIEW_MODULE = 'Magento_Review';
    const MODULE = 'Lipscore_RatingsReviews';

    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getData('block');
        $module = $block->getModuleName();

        $isLipscore      = ($module == self::MODULE);
        $isMagentoReview = ($module == self::MAGENTO_REVIEW_MODULE);

        if (!$isLipscore && !$isMagentoReview) {
            return;
        }

        $lipscoreEnabled = $this->moduleHelper()->isLipscoreOutputEnabled();
        $disableLipscore = $isLipscore && !$lipscoreEnabled;

        if ($disableLipscore || $isMagentoReview) {
            $blockName = $block->getNameInLayout();
            $block->getLayout()->renameElement($blockName, $blockName . '_ls_hidden');
        }
    }

    protected function methodAvailable()
    {
        return true;
    }
}
