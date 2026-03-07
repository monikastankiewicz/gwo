<?php

declare(strict_types=1);

namespace App\Component\Order\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class CartItemQuantityOutOfRange extends HttpException
{
    public function __construct(int $min, int $max, int $given)
    {
        parent::__construct(422, sprintf('Quantity must be between %d and %d. Given: %d.', $min, $max, $given));
    }
}