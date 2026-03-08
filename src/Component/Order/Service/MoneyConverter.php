<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\ValueObject\Currency;

final class MoneyConverter
{
    private const PLN_TO_EUR_RATE = '0.23';

    public function convertFromPln(int $amountInGrosze, Currency $targetCurrency): int
    {
        return match ($targetCurrency) {
            Currency::PLN => $amountInGrosze,
            Currency::EUR => $this->convertPlnToEurCents($amountInGrosze),
        };
    }

    private function convertPlnToEurCents(int $amountInGrosze): int
    {
        $amountInPln = $amountInGrosze / 100;
        $amountInEur = $amountInPln * (float) self::PLN_TO_EUR_RATE;

        return (int) round($amountInEur * 100);
    }
}