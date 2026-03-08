<?php

declare(strict_types=1);

namespace App\Component\Order\Model;

class OrderItemPricingData
{
    public function __construct(
        public readonly ?int $discount,
        public readonly int  $discountValue,
        public readonly int  $distributedOrderDiscountValue,
        public readonly int  $discountedUnitPrice,
        public readonly int  $total,
        public readonly ?int $taxValue,
    ) {
    }
}