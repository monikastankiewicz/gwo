<?php

declare(strict_types=1);

namespace App\Component\Order\Entity;

use App\Component\Order\ValueObject\Quantity;
use App\Component\Product\Entity\Product;
use Symfony\Component\Serializer\Annotation\Groups;

class OrderItem
{
    #[Groups(['order:read'])]
    protected int $id;

    protected ?Order $order;

    #[Groups(['order:read'])]
    protected ?Product $product;

    #[Groups(['order:read'])]
    protected int $quantity;

    #[Groups(['order:read'])]
    protected int $unitPrice;
    protected ?int $taxValue;

    #[Groups(['order:read'])]
    protected int $total = 0;

    public static function createForProduct(Product $product, Quantity $quantity): self
    {
        $item = new self();
        $item->product = $product;
        $item->unitPrice = $product->getPrice();
        $item->quantity = $quantity->toInt();
        $item->recalculateTotal();

        return $item;
    }

    public function increaseQuantity(Quantity $quantityToAdd): void
    {
        $newQuantity = Quantity::fromInt($this->quantity)->add($quantityToAdd);

        $this->quantity = $newQuantity->toInt();
        $this->recalculateTotal();
    }

    public function matchesProduct(Product $product): bool
    {
        return $this->product?->getId() === $product->getId();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getSubtotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function getTaxValue(): ?int
    {
        return $this->taxValue;
    }

    public function setTaxValue(?int $taxValue): void
    {
        $this->taxValue = $taxValue;
    }

    public function recalculateTotal(): void
    {
        $this->total = $this->getSubtotal();
    }
}
