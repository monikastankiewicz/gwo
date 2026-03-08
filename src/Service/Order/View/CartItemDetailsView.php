<?php

declare(strict_types=1);

namespace App\Service\Order\View;

use App\Component\Order\Entity\OrderItem;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CartItemDetailsView',
    required: [
        'id',
        'product',
        'unitPrice',
        'discountValue',
        'distributedOrderDiscountValue',
        'discountedUnitPrice',
        'quantity',
        'total',
    ]
)]
final class CartItemDetailsView
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public int $id,

        #[OA\Property(ref: '#/components/schemas/ProductDetailsView')]
        public ProductDetailsView $product,

        #[OA\Property(
            description: 'Price of a single item',
            type: 'integer',
            example: 1000
        )]
        public int $unitPrice,

        #[OA\Property(
            description: 'Percentage value of assigned discount to this item (null if there is no discount)',
            type: 'integer',
            example: 10,
            nullable: true
        )]
        public ?int $discount,

        #[OA\Property(
            description: 'Discounted amount assigned to the single item',
            type: 'integer',
            example: 100
        )]
        public int $discountValue,

        #[OA\Property(
            description: 'Discount amount for the entire order divided into one item',
            type: 'integer',
            example: 50
        )]
        public int $distributedOrderDiscountValue,

        #[OA\Property(
            description: 'Price for a single item with all discounts applied',
            type: 'integer',
            example: 850
        )]
        public int $discountedUnitPrice,

        #[OA\Property(
            description: 'Number of product items',
            type: 'integer',
            example: 2
        )]
        public int $quantity,

        #[OA\Property(
            description: 'Discounted unit price multiplied by the number of items',
            type: 'integer',
            example: 1700
        )]
        public int $total,

        #[OA\Property(
            description: 'Amount of tax calculated from "total" and product tax rate (null if tax-free)',
            type: 'integer',
            example: 391,
            nullable: true
        )]
        public ?int $taxValue,
    ) {
    }

    public static function fromOrderItem(
        OrderItem $item,
        int $unitPrice,
        ?int $discount,
        int $discountValue,
        int $distributedOrderDiscountValue,
        int $discountedUnitPrice,
        int $total,
        ?int $taxValue,
    ): self {
        return new self(
            id: $item->getId(),
            product: ProductDetailsView::fromProduct($item->getProduct()),
            unitPrice: $unitPrice,
            discount: $discount,
            discountValue: $discountValue,
            distributedOrderDiscountValue: $distributedOrderDiscountValue,
            discountedUnitPrice: $discountedUnitPrice,
            quantity: $item->getQuantity(),
            total: $total,
            taxValue: $taxValue,
        );
    }
}