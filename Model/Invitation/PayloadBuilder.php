<?php

namespace Lipscore\RatingsReviews\Model\Invitation;

use Lipscore\RatingsReviews\Helper\Product as ProductHelper;
use Lipscore\RatingsReviews\Helper\Reminder as ReminderHelper;
use Magento\Framework\App\Area;
use Magento\Framework\View\DesignInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds invitations in the order store's frontend context, so product URLs, images and translations
 * are the same for every trigger (storefront, admin, API, cron, bulk action).
 */
class PayloadBuilder
{
    /** @var ReminderHelper */
    private $reminderHelper;

    /** @var Emulation */
    private $emulation;

    /** @var ProductHelper */
    private $productHelper;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var DesignInterface */
    private $design;

    /**
     * @param ReminderHelper $reminderHelper
     * @param Emulation $emulation
     * @param ProductHelper $productHelper
     * @param StoreManagerInterface $storeManager
     * @param DesignInterface $design
     */
    public function __construct(
        ReminderHelper $reminderHelper,
        Emulation $emulation,
        ProductHelper $productHelper,
        StoreManagerInterface $storeManager,
        DesignInterface $design
    ) {
        $this->reminderHelper = $reminderHelper;
        $this->emulation = $emulation;
        $this->productHelper = $productHelper;
        $this->storeManager = $storeManager;
        $this->design = $design;
    }

    /**
     * Build invitations of one store's orders; an order that fails gets its exception instead of the payload.
     *
     * @param Order[] $orders Orders of the store
     * @param int $storeId
     * @return array [order ID => invitation array|\Throwable]
     */
    public function build(array $orders, $storeId)
    {
        $storeId = (int) $storeId;

        // Product data is cached per product ID, not per store.
        $this->productHelper->resetCache();

        $emulated = false;
        if (!$this->isStoreFrontend($storeId)) {
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            // Emulation does not nest: if another one is active, the call above is a no-op and stopping would end it.
            $emulated = $this->isStoreFrontend($storeId);
        }

        try {
            $payloads = [];
            foreach ($orders as $order) {
                try {
                    $payloads[(int) $order->getId()] = $this->reminderHelper->invitationData($order);
                } catch (\Throwable $e) {
                    $payloads[(int) $order->getId()] = $e;
                }
            }
        } finally {
            if ($emulated) {
                $this->emulation->stopEnvironmentEmulation();
            }
        }

        return $payloads;
    }

    /**
     * Whether we are already in the store's frontend.
     *
     * @param int $storeId
     * @return bool
     */
    private function isStoreFrontend($storeId)
    {
        return (int) $this->storeManager->getStore()->getId() === $storeId
            && $this->design->getArea() === Area::AREA_FRONTEND;
    }
}
