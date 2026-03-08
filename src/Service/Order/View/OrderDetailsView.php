<?php

declare(strict_types=1);

namespace App\Service\Order\View;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderDetailsView',
    required: ['id', 'itemsTotal', 'adjustmentsTotal', 'total', 'items']
)]
class OrderDetailsView
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 123)]
        public int $id,

        #[OA\Property(
            description: 'Sum of all items values (including items discounts)',
            type: 'integer',
            example: 1800
        )]
        public int $itemsTotal,

        #[OA\Property(
            description: 'Sum of all additional fees or discounts (discounts are represented as negative value)',
            type: 'integer',
            example: -200
        )]
        public int $adjustmentsTotal,

        #[OA\Property(
            description: 'Sum of items tax values (if all items are tax-free the value should be null)',
            type: 'integer',
            example: 414,
            nullable: true
        )]
        public ?int $taxTotal,

        #[OA\Property(
            description: 'Final price to pay (sum of "itemsTotal" and "adjustmentsTotal")',
            type: 'integer',
            example: 1600
        )]
        public int $total,

        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItemDetailsView')
        )]
        public array $items,
    ) {
    }
}