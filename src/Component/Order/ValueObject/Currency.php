<?php

declare(strict_types=1);

namespace App\Component\Order\ValueObject;

enum Currency: string
{
    case PLN = 'PLN';
    case EUR = 'EUR';

    public static function fromNullable(?string $value): self
    {
        return match (strtoupper((string) $value)) {
            'EUR' => self::EUR,
            'PLN', '' => self::PLN,
            default => throw new \InvalidArgumentException('Unsupported currency. Allowed values: PLN, EUR.'),
        };
    }
}