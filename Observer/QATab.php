<?php

namespace Lipscore\RatingsReviews\Observer;

use Lipscore\RatingsReviews\Block\Product\QA\Single;
use Magento\Framework\View\Layout;

class QATab extends AbstractObserver
{
    /**
     * @var string
     */
    protected static $logFile = 'ls_qa_tab_observer';

    /**
     * Add the Lipscore Q&A block to the product view layout.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->checkIfEnabled()) {
            return;
        }

        /** @var Layout $layout */
        $layout = $observer->getData('layout');
        if (!$layout) {
            return;
        }

        $layoutHandles = $layout->getUpdate()->getHandles();
        $properLayout  = in_array('catalog_product_view', $layoutHandles);

        if (!$properLayout) {
            return;
        }

        if ($this->config->canShowQa() && $this->checkIfBlockContentIsEmpty($layout, 'lipscore_qa.tab')) {
            $layout->addBlock(
                Single::class,
                'qa.single',
                'content',
                'lipscore_qa_single'
            );
        }
    }

    /**
     * Check whether the observer method is available to run.
     *
     * @return bool
     */
    protected function methodAvailable()
    {
        return $this->config->isActive();
    }
}
