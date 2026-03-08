<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Component\Order\Entity\Order;
use App\Component\Order\Exception\OrderNotFound;
use App\Component\Promotion\Entity\Promotion;
use App\Component\Promotion\Exception\PromotionNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class AddPromotionToCartHandler
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function handle(int $orderId, AddPromotionToCart $cmd): Order
    {
        $cart = $this->doctrine->getRepository(Order::class)->find($orderId);
        if (!$cart instanceof Order) {
            throw new OrderNotFound();
        }

        $promotion = $this->doctrine->getRepository(Promotion::class)->find($cmd->promotionId);
        if (!$promotion instanceof Promotion) {
            throw new PromotionNotFound();
        }

        $cart->addPromotion($promotion);
        $this->em->flush();

        return $cart;
    }
}