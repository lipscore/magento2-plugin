<?php

namespace Lipscore\RatingsReviews\Helper;

use Magento\Sales\Model\Order;

class Purchase extends AbstractHelper
{
    /**
     * Get the customer email from the order, falling back to the customer's own email.
     *
     * @param Order $order
     * @return string
     */
    public function customerEmail(Order $order)
    {
        $email = $order->getBillingAddress()->getEmail();
        if (!$email) {
            $email = $order->getCustomerEmail();
        }

        return $email;
    }

    /**
     * Get the customer's full name from the order billing address, falling back to the customer's own name.
     *
     * @param Order $order
     * @return string
     */
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
     * Get the order creation timestamp.
     *
     * @param Order $order
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
