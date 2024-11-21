<?php

namespace Lipscore\RatingsReviews\Observer;

class QATab extends AbstractObserver
{
    protected static $logFile = 'ls_qa_tab_observer';


    protected function _execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getData('layout');

        if (!$layout) {
            return;
        }

        if ($this->config->canShowQa() && $layout->hasElement('lipscore_qa.tab')) {
          $layout->unsetElement('lipscore_qa.tab');
          return;
        }

        $layoutHandles = $layout->getUpdate()->getHandles();
        $properLayout  = in_array('catalog_product_view', $layoutHandles);

        if (!$properLayout) {
            return;
        }

        if (!$layout->hasElement('lipscore_qa.tab')) {
            $layout->addBlock(
                \Lipscore\RatingsReviews\Block\Product\QA\Single::class,
                'qa.single',
                'content',
                'lipscore_qa_single'
            );
        }
    }

    protected function methodAvailable()
    {
        return $this->config->isActive();
    }
}
