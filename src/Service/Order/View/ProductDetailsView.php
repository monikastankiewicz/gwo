<?php

declare(strict_types=1);

namespace App\Service\Order\View;

use App\Component\Product\Entity\Product;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductDetailsView',
    description: 'Details of the product assigned to the item'
)]
final class ProductDetailsView
{
    public function __construct(
        #[OA\Property(type: 'string')]
        public string $code,

        #[OA\Property(type: 'string')]
        public string $name,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        return new self(
            code: $product->getCode(),
            name: $product->getName(),
        );
    }
}