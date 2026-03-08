<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Component\Order\Entity\Order;
use App\Component\Order\Exception\OrderNotFound;
use App\Component\Order\ValueObject\Currency;
use App\Service\Order\View\CartDetailsView;
use App\Service\Order\View\CartDetailsViewFactory;
use Doctrine\Persistence\ManagerRegistry;

final class GetCartDetailsHandler
{
    public function __construct(
        private readonly ManagerRegistry         $doctrine,
        private readonly CartDetailsViewFactory  $cartDetailsViewFactory,
    ) {
    }

    public function handle(int $cartId, Currency $currency): CartDetailsView
    {
        $cart = $this->doctrine->getRepository(Order::class)->find($cartId);

        if (!$cart instanceof Order) {
            throw new OrderNotFound();
        }

        return $this->cartDetailsViewFactory->create($cart, $currency);
    }
}