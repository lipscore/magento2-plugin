<?php

namespace Lipscore\RatingsReviews\Observer;

use Magento\Framework\View\Element\Template;

class Output extends AbstractObserver
{
    const MAGENTO_REVIEW_MODULE = 'Magento_Review';
    const MODULE = 'Lipscore_RatingsReviews';

    protected static $logFile = 'ls_output_observer';

    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->checkIfEnabled()) {
            return;
        }

        $block = $observer->getData('block');
        $module = $block->getModuleName();

        $isLipscore      = ($module == self::MODULE);
        $isMagentoReview = ($module == self::MAGENTO_REVIEW_MODULE);

        if (!$isLipscore && !$isMagentoReview) {
            return;
        }

        $blockName       = $block->getNameInLayout();
        $lipscoreEnabled = $this->config->isLipscoreOutputEnabled();
        $hideLipscore    = $isLipscore && !$lipscoreEnabled;
        $hideMagento     = $isMagentoReview && $lipscoreEnabled;
        //compatibility with different themes
        $isNotReviewTab  = $blockName !== 'reviews.tab';

        if (($hideMagento || $hideLipscore) && $isNotReviewTab) {
            $block->getLayout()->renameElement($blockName, $blockName . '_ls_hidden');
        }
    }

    protected function methodAvailable()
    {
        return true;
    }
}
