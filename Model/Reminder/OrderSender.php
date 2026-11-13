<?php

namespace Lipscore\RatingsReviews\Model\Reminder;

use Lipscore\RatingsReviews\Helper\Product as ProductHelper;
use Lipscore\RatingsReviews\Model\Reminder;
use Magento\Framework\App\Area;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Sends a review request in the order store's frontend context, so the payload is the same for every trigger.
 */
class OrderSender
{
    /** @var Reminder */
    protected $reminder;

    /** @var Emulation */
    protected $emulation;

    /** @var ProductHelper */
    protected $productHelper;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var DesignInterface */
    protected $design;

    /**
     * @param Reminder $reminder
     * @param Emulation $emulation
     * @param ProductHelper $productHelper
     * @param StoreManagerInterface $storeManager
     * @param DesignInterface $design
     */
    public function __construct(
        Reminder $reminder,
        Emulation $emulation,
        ProductHelper $productHelper,
        StoreManagerInterface $storeManager,
        DesignInterface $design
    ) {
        $this->reminder = $reminder;
        $this->emulation = $emulation;
        $this->productHelper = $productHelper;
        $this->storeManager = $storeManager;
        $this->design = $design;
    }

    /**
     * Sends the review request.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return mixed false on failure
     */
    public function send($order)
    {
        $storeId = (int) $order->getStoreId();

        // Product data is cached per product ID, not per store.
        $this->productHelper->resetCache();

        if ($this->isStoreFrontend($storeId)) {
            return $this->reminder->send($order);
        }

        $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        // Emulation does not nest: if another one is active, the call above is a no-op and stopping would end it.
        $emulated = $this->isStoreFrontend($storeId);
        try {
            return $this->reminder->send($order);
        } finally {
            if ($emulated) {
                $this->emulation->stopEnvironmentEmulation();
            }
        }
    }

    /**
     * Whether we are already in the store's frontend.
     *
     * @param int $storeId
     * @return bool
     */
    protected function isStoreFrontend($storeId)
    {
        return (int) $this->storeManager->getStore()->getId() === $storeId
            && $this->design->getArea() === Area::AREA_FRONTEND;
    }
}
