<?php
declare(strict_types=1);

namespace App\Component\Order\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OrderNotFound extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Order not found.');
    }
}