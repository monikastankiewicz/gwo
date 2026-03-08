<?php
declare(strict_types=1);

namespace App\Component\Order\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class OrderPromotionAlreadyApplied extends HttpException
{
    public function __construct()
    {
        parent::__construct(422, 'An order-type promotion has already been applied to this order.');
    }
}