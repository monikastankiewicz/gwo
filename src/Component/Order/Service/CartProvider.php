<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\Order\Repository\OrderRepository;
use App\Component\User\Entity\User;

final class CartProvider
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {
    }

    public function getOrCreateCartForUser(User $user): Order
    {
        $cart = $this->orderRepository->findActiveCartForUser($user);

        if ($cart) {
            return $cart;
        }

        $cart = Order::createCartForUser($user);
        $this->orderRepository->save($cart);

        return $cart;
    }
}