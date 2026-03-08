<?php

declare(strict_types=1);

namespace App\Component\Order\Entity;

use App\Component\Order\Exception\CartDistinctProductsLimitExceeded;
use App\Component\Order\Exception\OrderPromotionAlreadyApplied;
use App\Component\Order\ValueObject\OrderStatus;
use App\Component\Order\ValueObject\Quantity;
use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use App\Component\Resource\Model\Timestamped;
use App\Component\Resource\Model\TimestampedTrait;
use App\Component\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

class Order implements Timestamped
{
    use TimestampedTrait;

    public const MAX_DISTINCT_PRODUCTS = 5;

    #[Groups(['order:read'])]
    protected int $id;

    #[Groups(['order:read'])]
    protected OrderStatus $status;

    protected User $user;

    #[Groups(['order:read'])]
    protected int $itemsTotal = 0;

    #[Groups(['order:read'])]
    protected int $adjustmentsTotal = 0;

    /** Items total + adjustments total. */
    #[Groups(['order:read'])]
    protected int $total = 0;

    /** @var Collection<array-key, OrderItem> */
    #[Groups(['order:read'])]
    protected Collection $items;

    /** @var Collection<array-key, OrderPromotion> */
    protected Collection $orderPromotions;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->orderPromotions = new ArrayCollection();
        $this->status = OrderStatus::CART;
    }

    public static function createCartForUser(User $user): self
    {
        $order = new self();
        $order->setUser($user);
        $order->setStatus(OrderStatus::CART);

        return $order;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getItemsTotal(): int
    {
        return $this->itemsTotal;
    }

    public function getAdjustmentsTotal(): int
    {
        return $this->adjustmentsTotal;
    }

    public function setAdjustmentsTotal(int $adjustmentsTotal): void
    {
        $this->adjustmentsTotal = $adjustmentsTotal;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    /** @return Collection<array-key, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function clearItems(): void
    {
        $this->items->clear();

        $this->recalculateItemsTotal();
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->hasItem($item)) {
            return;
        }

        $this->items->add($item);
        $item->setOrder($this);

        $this->recalculateItemsTotal();
    }

    public function removeItem(OrderItem $item): void
    {
        if (!$this->hasItem($item)) {
            return;
        }

        $this->items->removeElement($item);
        $item->setOrder(null);

        $this->recalculateItemsTotal();
    }

    public function hasItem(OrderItem $item): bool
    {
        return $this->items->contains($item);
    }

    /** Items total + Adjustments total. */
    protected function recalculateTotal(): void
    {
        $this->total = $this->itemsTotal + $this->adjustmentsTotal;

        if ($this->total < 0) {
            $this->total = 0;
        }
    }

    protected function recalculateItemsTotal(): void
    {
        $this->itemsTotal = 0;
        foreach ($this->items as $item) {
            $this->itemsTotal += $item->getTotal();
        }

        $this->recalculateTotal();
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): void
    {
        $this->status = $status;
    }

    public function addProduct(Product $product, Quantity $quantityToAdd): void
    {
        $existingItem = $this->findItemForProduct($product);

        if ($existingItem !== null) {
            $existingItem->increaseQuantity($quantityToAdd);
            $this->recalculateItemsTotal();

            return;
        }

        $this->assertDistinctProductsLimitNotExceeded();

        $this->addItem(OrderItem::createForProduct($product, $quantityToAdd));
    }

    /** Finds an existing order item for the given product. */
    private function findItemForProduct(Product $product): ?OrderItem
    {
        foreach ($this->items as $item) {
            if ($item->matchesProduct($product)) {
                return $item;
            }
        }

        return null;
    }

    private function assertDistinctProductsLimitNotExceeded(): void
    {
        if ($this->items->count() >= self::MAX_DISTINCT_PRODUCTS) {
            throw new CartDistinctProductsLimitExceeded(self::MAX_DISTINCT_PRODUCTS);
        }
    }

    /** @return Collection<array-key, OrderPromotion> */
    public function getOrderPromotions(): Collection
    {
        return $this->orderPromotions;
    }

    public function addPromotion(Promotion $promotion): void
    {
        if ($this->orderPromotions->contains($promotion)) {
            return;
        }

        $this->assertItemPromotionCanBeApplied($promotion);

        $this->getOrderPromotions()->add(OrderPromotion::create($this, $promotion));
        $this->recalculateItemsTotal();
    }

    private function assertItemPromotionCanBeApplied(Promotion $promotion): void
    {
        // Prevent adding the same promotion to the order more than once
        if ($promotion->getType() !== Promotion::TYPE_ORDER) {
            return;
        }

        // Only one order-type promotion (TYPE_ORDER) can be applied to an order
        foreach ($this->getOrderPromotions() as $orderPromotion) {
            if ($orderPromotion->getPromotion()->getType() === Promotion::TYPE_ORDER) {
                throw new OrderPromotionAlreadyApplied();
            }
        }
    }
}
