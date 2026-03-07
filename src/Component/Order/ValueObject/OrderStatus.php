<?php

declare(strict_types=1);

namespace App\Component\Order\ValueObject;

/**
 * Represents the current status of an order.
 */
enum OrderStatus: string
{
    /**
     * The order is in the user's cart and has not been placed yet.
     */
    case CART = 'cart';

    /**
     * The order has been placed by the user.
     */
    case PLACED = 'placed';
}