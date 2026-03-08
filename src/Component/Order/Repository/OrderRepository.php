<?php

namespace App\Component\Order\Repository;

use App\Component\Order\Entity\Order;
use App\Component\Order\ValueObject\OrderStatus;
use App\Component\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $order): Order
    {
        $entityManager = $this->getEntityManager();

        if ($order->isNew()) {
            $entityManager->persist($order);
        }

        $entityManager->flush();

        return $order;
    }

    public function findActiveCartForUser(User $user): ?Order
    {
        return $this->findOneBy(['user' => $user, 'status' => OrderStatus::CART]);
    }
}