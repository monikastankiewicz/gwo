<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\Order\Entity\OrderItem;
use App\Component\Order\Model\ItemDiscountData;
use App\Component\Order\Model\OrderItemPricingData;
use App\Component\Promotion\Entity\Promotion;

final class OrderItemPricingCalculator
{
    public function calculate(OrderItem $item, Order $order): OrderItemPricingData
    {
        $product = $item->getProduct();
        $orderPromotions = $order->getOrderPromotions()->toArray();

        $itemDiscountData = $this->calculateItemDiscount(
            unitPrice: $item->getUnitPrice(),
            productType: $product->getType(),
            orderPromotions: $orderPromotions,
        );

        $distributedOrderDiscountValue = $this->calculateDistributedOrderDiscountValue(
            item: $item,
            order: $order,
            orderPromotions: $orderPromotions,
        );

        $discountedUnitPrice = $this->calculateDiscountedUnitPrice(
            priceAfterItemDiscount: $itemDiscountData->priceAfterDiscount,
            distributedOrderDiscountValue: $distributedOrderDiscountValue,
        );

        $total = $this->calculateTotal(
            discountedUnitPrice: $discountedUnitPrice,
            quantity: $item->getQuantity(),
        );

        $taxValue = $this->calculateTaxValue(
            total: $total,
            taxRate: $product->getTaxRate(),
        );

        return new OrderItemPricingData(
            discount: $itemDiscountData->discount,
            discountValue: $itemDiscountData->discountValue,
            distributedOrderDiscountValue: $distributedOrderDiscountValue,
            discountedUnitPrice: $discountedUnitPrice,
            total: $total,
            taxValue: $taxValue,
        );
    }

    private function calculateItemDiscount(int $unitPrice, string $productType, array $orderPromotions): ItemDiscountData
    {
        $currentPrice = $unitPrice;

        // Apply item promotions in creation order
        usort($orderPromotions, static function ($left, $right): int {
            return $left->getCreatedAt() <=> $right->getCreatedAt();
        });

        foreach ($orderPromotions as $orderPromotion) {
            $promotion = $orderPromotion->getPromotion();
            // Only item-level promotions affect unit price
            if ($promotion->getType() !== Promotion::TYPE_ITEM) {
                continue;
            }

            $filter = $promotion->getProductTypesFilter();
            // Skip promotion if product type is not eligible
            if ($filter === null || !in_array($productType, $filter, true)) {
                continue;
            }

            // Apply percentage discount to the current price
            $percentage = $promotion->getPercentageDiscount();
            $discountAmount = (int)floor($currentPrice * $percentage / 100);
            $currentPrice -= $discountAmount;
        }

        $discountValue = $unitPrice - $currentPrice ?? 0;

        // Effective discount percentage is rounded, so it may slightly differ from the exact calculated value.
        $discount = $discountValue > 0 ? (int)round(($discountValue * 100) / $unitPrice) : null;

        return new ItemDiscountData(
            discount: $discount,
            discountValue: $discountValue,
            priceAfterDiscount: $currentPrice,
        );
    }

    private function calculateDistributedOrderDiscountValue(OrderItem $item, Order $order, array $orderPromotions): int
    {
        $orderPromotionPercentage = null;

        // Find ORDER type promotion and get its percentage discount
        foreach ($orderPromotions as $orderPromotion) {
            $promotion = $orderPromotion->getPromotion();
            if ($promotion->getType() !== Promotion::TYPE_ORDER) {
                continue;
            }
            $orderPromotionPercentage = $promotion->getPercentageDiscount();
            break;
        }

        $orderSubtotalAfterItemDiscount = 0;
        $itemSubtotalAfterItemDiscount = 0;

        // Calculate order subtotal after item-level discounts
        foreach ($order->getItems() as $orderItem) {
            $itemDiscountData = $this->calculateItemDiscount(
                unitPrice: $orderItem->getUnitPrice(),
                productType: $orderItem->getProduct()->getType(),
                orderPromotions: $orderPromotions,
            );

            $subtotalAfterItemDiscount = $itemDiscountData->priceAfterDiscount * $orderItem->getQuantity();
            $orderSubtotalAfterItemDiscount += $subtotalAfterItemDiscount;

            if ($orderItem === $item) {
                $itemSubtotalAfterItemDiscount = $subtotalAfterItemDiscount;
            }
        }

        if ($orderSubtotalAfterItemDiscount === 0) {
            return 0;
        }

        // Calculate total order discount based on percentage
        $orderDiscountTotal = (int) floor($orderSubtotalAfterItemDiscount * $orderPromotionPercentage / 100);

        // Distribute order discount proportionally to this item
        $itemOrderDiscountTotal = (int) floor($orderDiscountTotal * $itemSubtotalAfterItemDiscount / $orderSubtotalAfterItemDiscount);

        return (int) floor($itemOrderDiscountTotal / $item->getQuantity());
    }

    private function calculateDiscountedUnitPrice(int $priceAfterItemDiscount, int $distributedOrderDiscountValue): int
    {
        return $priceAfterItemDiscount - $distributedOrderDiscountValue;
    }

    private function calculateTotal(int $discountedUnitPrice, int $quantity): int
    {
        return $discountedUnitPrice * $quantity;
    }

    private function calculateTaxValue(int $total, ?int $taxRate): ?int
    {
        if ($taxRate === null) {
            return null;
        }

        return (int)floor($total * $taxRate / 100);
    }
}