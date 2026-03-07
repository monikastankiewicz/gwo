<?php
declare(strict_types=1);

namespace App\Component\Order\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class CartDistinctProductsLimitExceeded extends HttpException
{
    public function __construct(int $max)
    {
        parent::__construct(422, sprintf('Cart cannot contain more than %d different products.', $max));
    }
}