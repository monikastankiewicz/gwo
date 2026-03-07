<?php

declare(strict_types=1);

namespace App\Component\Order\ValueObject;

use App\Component\Order\Exception\CartItemQuantityOutOfRange;

/**
 * Represents the quantity of a cart item.
 */
final class Quantity
{
    /**
     * Minimum allowed quantity of an item in the cart.
     */
    public const MIN = 1;

    /**
     * Maximum allowed quantity of an item in the cart.
     */
    public const MAX = 10;

    private function __construct(
        private readonly int $value,
    ) {
    }

    public static function fromInt(int $value): self
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new CartItemQuantityOutOfRange(self::MIN, self::MAX, $value);
        }

        return new self($value);
    }

    public function add(self $other): self
    {
        return self::fromInt($this->value + $other->value);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}