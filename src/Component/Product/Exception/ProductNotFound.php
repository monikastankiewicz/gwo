<?php
declare(strict_types=1);

namespace App\Component\Product\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductNotFound extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Product not found.');
    }
}