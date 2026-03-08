<?php

declare(strict_types=1);

namespace App\Service\Order\View;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\OrderItemPricingCalculator;

final class OrderDetailsViewFactory
{
    public function __construct(
        private readonly OrderItemPricingCalculator $orderItemPricingCalculator
    ) {
    }

    public function create(Order $order): OrderDetailsView
    {
        $items = [];
        $taxTotal = 0;
        $hasTax = false;
        $itemsTotal = 0;
        $adjustmentsTotal = 0;
        $total = 0;

        foreach ($order->getItems() as $item) {
            $pricing = $this->orderItemPricingCalculator->calculate($item, $order);
            $itemView = OrderItemDetailsView::fromOrderItem($item, $pricing);

            $items[] = $itemView;

            $itemsTotal += ($itemView->unitPrice - $itemView->discountValue) * $itemView->quantity;
            $adjustmentsTotal -= $itemView->distributedOrderDiscountValue * $itemView->quantity;
            $total += $itemView->total;

            if ($itemView->taxValue !== null) {
                $hasTax = true;
                $taxTotal += $itemView->taxValue;
            }
        }

        return new OrderDetailsView(
            id:               $order->getId(),
            itemsTotal:       $itemsTotal,
            adjustmentsTotal: $adjustmentsTotal,
            taxTotal:         $hasTax ? $taxTotal : null,
            total:            $total,
            items:            $items,
        );
    }
}