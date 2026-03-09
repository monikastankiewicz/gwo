<?php

declare(strict_types=1);

namespace App\Service\Order\View;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\MoneyConverter;
use App\Component\Order\Service\OrderItemPricingCalculator;
use App\Component\Order\ValueObject\Currency;

/**
 * Creates a view model representing cart details with calculated prices,
 * applied discounts, taxes and currency conversion.
 */
final class CartDetailsViewFactory
{
    public function __construct(
        private readonly OrderItemPricingCalculator $orderItemPricingCalculator,
        private readonly MoneyConverter $moneyConverter,
    ) {
    }

    public function create(Order $order, Currency $currency): CartDetailsView
    {
        $items = [];
        $taxTotal = 0;
        $hasTax = false;
        $itemsTotal = 0;
        $adjustmentsTotal = 0;
        $total = 0;

        foreach ($order->getItems() as $item) {
            $pricing = $this->orderItemPricingCalculator->calculate($item, $order);

            $unitPrice = $this->convertAmount($item->getUnitPrice(), $currency);
            $discountValue = $this->convertAmount($pricing->discountValue, $currency);
            $distributedOrderDiscountValue = $this->convertAmount($pricing->distributedOrderDiscountValue, $currency);
            $discountedUnitPrice = $this->convertAmount($pricing->discountedUnitPrice, $currency);
            $itemTotal = $this->convertAmount($pricing->total, $currency);
            $taxValue = $pricing->taxValue !== null ? $this->convertAmount($pricing->taxValue, $currency) : null;

            $itemView = CartItemDetailsView::fromOrderItem(
                item: $item,
                unitPrice: $unitPrice,
                discount: $pricing->discount,
                discountValue: $discountValue,
                distributedOrderDiscountValue: $distributedOrderDiscountValue,
                discountedUnitPrice: $discountedUnitPrice,
                total: $itemTotal,
                taxValue: $taxValue,
            );

            $items[] = $itemView;

            $itemsTotal += ($unitPrice - $discountValue) * $item->getQuantity();
            $adjustmentsTotal -= $distributedOrderDiscountValue * $item->getQuantity();
            $total += $itemTotal;

            if ($taxValue !== null) {
                $hasTax = true;
                $taxTotal += $taxValue;
            }
        }

        return new CartDetailsView(
            id: $order->getId(),
            itemsTotal: $itemsTotal,
            adjustmentsTotal: $adjustmentsTotal,
            taxTotal: $hasTax ? $taxTotal : null,
            total: $total,
            items: $items,
        );
    }

    private function convertAmount(int $amount, Currency $currency): int
    {
        return $this->moneyConverter->convertFromPln($amount, $currency);
    }
}