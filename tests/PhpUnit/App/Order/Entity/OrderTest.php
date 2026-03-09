<?php

namespace App\Tests\PhpUnit\App\Order\Entity;

use App\Component\Order\Entity\Order;
use App\Component\Order\Entity\OrderItem;
use App\Component\Order\Exception\CartDistinctProductsLimitExceeded;
use App\Component\Order\Exception\CartItemQuantityOutOfRange;
use App\Component\Order\Exception\OrderPromotionAlreadyApplied;
use App\Component\Order\ValueObject\Quantity;
use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use App\Component\User\Entity\User;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class OrderTest extends TestCase
{
    public function test_order_total_calculation(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setQuantity(2);
        $orderItem->setUnitPrice(99);
        $orderItem->recalculateTotal();

        $order = new Order();
        $order->addItem($orderItem);

        $this->assertSame(198, $order->getItemsTotal());
    }

    public function test_adds_product_to_cart(): void
    {
        $order = $this->createCart();
        $product = $this->createProduct(1, 1000);

        $order->addProduct($product, Quantity::fromInt(2));

        $item = $order->getItems()->first();

        $this->assertCount(1, $order->getItems());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(1000, $item->getUnitPrice());
        $this->assertSame(2000, $item->getTotal());
    }

    public function test_adding_same_product_increases_quantity(): void
    {
        $order = $this->createCart();
        $productPrice = 1000;
        $productQuantity = 3;
        $product = $this->createProduct(1, $productPrice);

        for ($i = 0; $i < $productQuantity; $i++) {
            $order->addProduct($product, Quantity::fromInt(1));
        }

        $this->assertCount(1, $order->getItems());

        $item = $order->getItems()->first();
        $this->assertSame($productQuantity, $item->getQuantity());
        $this->assertSame($productPrice * $productQuantity, $item->getTotal());
    }

    public function test_quantity_cannot_be_less_than_minimum(): void
    {
        $this->expectException(CartItemQuantityOutOfRange::class);

        Quantity::fromInt(Quantity::MIN - 1);
    }

    public function test_quantity_cannot_exceed_maximum(): void
    {
        $this->expectException(CartItemQuantityOutOfRange::class);

        Quantity::fromInt(Quantity::MAX + 1);
    }

    public function test_cart_cannot_have_more_than_five_distinct_products(): void
    {
        $order = $this->createCart();
        $maxDistinctProducts = Order::MAX_DISTINCT_PRODUCTS;
        $productPrice = 1000;

        $this->expectException(CartDistinctProductsLimitExceeded::class);

        for ($i = 1; $i <= $maxDistinctProducts + 1; $i++) {
            $order->addProduct(
                product: $this->createProduct($i, $productPrice),
                quantityToAdd: Quantity::fromInt(1)
            );
        }
    }

    public function test_can_add_promotion_type_order(): void
    {
        $order = $this->createCart();
        $promotion = $this->createPromotion(
            id: 1,
            type: Promotion::TYPE_ORDER,
            percentageDiscount: 20,
        );

        $order->addPromotion($promotion);

        $this->assertCount(1, $order->getOrderPromotions());

        $orderPromotion = $order->getOrderPromotions()->first();
        $this->assertSame($promotion, $orderPromotion->getPromotion());
    }

    public function test_cannot_add_different_promotion_type_order_when_order_promotion_is_already_applied(): void
    {
        $order = $this->createCart();

        $firstOrderPromotion = $this->createPromotion(
            id: 1,
            type: Promotion::TYPE_ORDER,
            percentageDiscount: 20,
        );

        $secondDifferentOrderPromotion = $this->createPromotion(
            id: 2,
            type: Promotion::TYPE_ORDER,
            percentageDiscount: 10,
        );

        $order->addPromotion($firstOrderPromotion);
        $this->expectException(OrderPromotionAlreadyApplied::class);
        $order->addPromotion($secondDifferentOrderPromotion);
    }

    public function test_can_add_multiple_promotions_type_item(): void
    {
        $order = $this->createCart();

        $firstPromotion = $this->createPromotion(
            id: 1,
            type: Promotion::TYPE_ITEM,
            percentageDiscount: 10,
            productTypesFilter: [Product::TYPE_BOOK],
        );

        $secondPromotion = $this->createPromotion(
            id: 2,
            type: Promotion::TYPE_ITEM,
            percentageDiscount: 5,
            productTypesFilter: [Product::TYPE_BOOK],
        );

        $order->addPromotion($firstPromotion);
        $order->addPromotion($secondPromotion);

        $this->assertCount(2, $order->getOrderPromotions());
    }

    private function createCart(): Order
    {
        return Order::createCartForUser($this->createUser());
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setName('Test user');

        return $user;
    }

    private function createProduct(int $id, int $price): Product
    {
        $product = new Product();
        $product->setCode('P' . $id);
        $product->setName('Product ' . $id);
        $product->setType(Product::TYPE_BOOK);
        $product->setPrice($price);

        $this->setEntityId($product, $id);

        return $product;
    }

    private function createPromotion(
        int $id,
        string $type,
        int $percentageDiscount,
        ?array $productTypesFilter = null,
    ): Promotion {
        $promotion = new Promotion();
        $promotion->setType($type);
        $promotion->setPercentageDiscount($percentageDiscount);
        $promotion->setProductTypesFilter($productTypesFilter);

        $this->setEntityId($promotion, $id);

        return $promotion;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}