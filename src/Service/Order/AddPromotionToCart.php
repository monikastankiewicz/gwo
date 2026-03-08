<?php

declare(strict_types=1);

namespace App\Service\Order;

use Symfony\Component\Validator\Constraints as Assert;

class AddPromotionToCart
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $promotionId
    ) {
    }
}