<?php

declare(strict_types=1);

namespace App\Component\Order\Model;

class ItemDiscountData
{
    public function __construct(
        public readonly ?int $discount,
        public readonly int  $discountValue,
        public readonly int  $priceAfterDiscount,
    ) {
    }
}