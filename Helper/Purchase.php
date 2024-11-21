<?php

namespace Lipscore\RatingsReviews\Helper;

use Magento\Sales\Model\Order;

class Purchase extends AbstractHelper
{
    public function customerEmail(Order $order)
    {
        $email = $order->getBillingAddress()->getEmail();
        if (!$email) {
            $email = $order->getCustomerEmail();
        }

        return $email;
    }

    public function customerName(Order $order)
    {
        $addr = $order->getBillingAddress();
        $name = $addr->getFirstname() . ' ' . $addr->getLastname();

        if (!trim($name)) {
            $name = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        }

        return $name;
    }

    /**
     * @return int
     */
    public function createdAt($order)
    {
        $date = $order->getCreatedAt();
        if ($date) {
            return strtotime($date);
        }
    }
}
