<?php
declare(strict_types=1);

namespace App\Component\Promotion\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PromotionNotFound extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Promotion not found.');
    }
}