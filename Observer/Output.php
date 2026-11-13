<?php

namespace Lipscore\RatingsReviews\Observer;

use Magento\Framework\View\Element\Template;

class Output extends AbstractObserver
{
    public const MAGENTO_REVIEW_MODULE = 'Magento_Review';
    public const MODULE = 'Lipscore_RatingsReviews';

    /**
     * @var string
     */
    protected static $logFile = 'ls_output_observer';

    /**
     * Hide the Magento or Lipscore review block based on module configuration.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
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

    /**
     * Check whether the observer method is available to run.
     *
     * @return bool
     */
    protected function methodAvailable()
    {
        return true;
    }
}
