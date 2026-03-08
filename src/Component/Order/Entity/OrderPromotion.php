<?php

declare(strict_types=1);

namespace App\Component\Order\Entity;

use App\Component\Promotion\Entity\Promotion;
use App\Component\Resource\Model\Timestamped;
use App\Component\Resource\Model\TimestampedTrait;

class OrderPromotion implements Timestamped
{
    use TimestampedTrait;

    protected int $id;

    protected Order $order;

    protected Promotion $promotion;

    public static function create(Order $order, Promotion $promotion): self
    {
        $orderPromotion = new self();
        $orderPromotion->setOrder($order);
        $orderPromotion->setPromotion($promotion);

        return $orderPromotion;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function getPromotion(): Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(Promotion $promotion): void
    {
        $this->promotion = $promotion;
    }
}