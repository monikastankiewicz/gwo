<?php

declare(strict_types=1);

namespace App\Tests\PhpUnit\App\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\Order\Entity\OrderItem;
use App\Component\Order\Entity\OrderPromotion;
use App\Component\Order\Service\OrderItemPricingCalculator;
use App\Component\Order\ValueObject\Quantity;
use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use App\Component\User\Entity\User;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

final class OrderItemPricingCalculatorTest extends TestCase
{
    private OrderItemPricingCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new OrderItemPricingCalculator();
    }

    public function test_calculates_two_products_with_single_item_promotion_for_one_product_type(): void
    {
        $order = $this->createOrder();

        $bookProduct = $this->createProduct(
            id: 1,
            price: 1000,
            type: Product::TYPE_BOOK,
            taxRate: 23,
        );

        $audioProduct = $this->createProduct(
            id: 2,
            price: 500,
            type: Product::TYPE_AUDIO,
            taxRate: 23,
        );

        $bookItem = OrderItem::createForProduct($bookProduct, Quantity::fromInt(1));
        $audioItem = OrderItem::createForProduct($audioProduct, Quantity::fromInt(1));

        $order->addItem($bookItem);
        $order->addItem($audioItem);

        $itemPromotion = $this->createPromotion(
            id: 1,
            type: Promotion::TYPE_ITEM,
            percentageDiscount: 10,
            productTypesFilter: [Product::TYPE_BOOK],
        );

        $order->getOrderPromotions()->add(OrderPromotion::create($order, $itemPromotion));

        $bookResult = $this->calculator->calculate($bookItem, $order);
        $audioResult = $this->calculator->calculate($audioItem, $order);

        // BOOK: 1000 - 10% = 900
        $this->assertSame(10, $bookResult->discount);
        $this->assertSame(100, $bookResult->discountValue);
        $this->assertSame(0, $bookResult->distributedOrderDiscountValue);
        $this->assertSame(900, $bookResult->discountedUnitPrice);
        $this->assertSame(900, $bookResult->total);
        $this->assertSame(207, $bookResult->taxValue);

        // AUDIO: no item promotion -> stays 500
        $this->assertNull($audioResult->discount);
        $this->assertSame(0, $audioResult->discountValue);
        $this->assertSame(0, $audioResult->distributedOrderDiscountValue);
        $this->assertSame(500, $audioResult->discountedUnitPrice);
        $this->assertSame(500, $audioResult->total);
        $this->assertSame(115, $audioResult->taxValue);
    }

    public function test_calculates_two_products_with_two_item_promotions_for_different_product_types(): void
    {
        $order = $this->createOrder();

        $bookProduct = $this->createProduct(
            id: 1,
            price: 1000,
            type: Product::TYPE_BOOK,
            taxRate: 23,
        );

        $audioProduct = $this->createProduct(
            id: 2,
            price: 500,
            type: Product::TYPE_AUDIO,
            taxRate: 23,
        );

        $bookItem = OrderItem::createForProduct($bookProduct, Quantity::fromInt(1));
        $audioItem = OrderItem::createForProduct($audioProduct, Quantity::fromInt(1));

        $order->addItem($bookItem);
        $order->addItem($audioItem);

        $bookPromotion = $this->createPromotion(
            id: 1,
            type: Promotion::TYPE_ITEM,
            percentageDiscount: 10,
            productTypesFilter: [Product::TYPE_BOOK],
        );

        $audioPromotion = $this->createPromotion(
            id: 2,
            type: Promotion::TYPE_ITEM,
            percentageDiscount: 20,
            productTypesFilter: [Product::TYPE_AUDIO],
        );

        $order->getOrderPromotions()->add(OrderPromotion::create($order, $bookPromotion));
        $order->getOrderPromotions()->add(OrderPromotion::create($order, $audioPromotion));

        $bookResult = $this->calculator->calculate($bookItem, $order);
        $audioResult = $this->calculator->calculate($audioItem, $order);

        // BOOK: 1000 - 10% = 900
        $this->assertSame(10, $bookResult->discount);
        $this->assertSame(100, $bookResult->discountValue);
        $this->assertSame(0, $bookResult->distributedOrderDiscountValue);
        $this->assertSame(900, $bookResult->discountedUnitPrice);
        $this->assertSame(900, $bookResult->total);
        $this->assertSame(207, $bookResult->taxValue);

        // AUDIO: 500 - 20% = 400
        $this->assertSame(20, $audioResult->discount);
        $this->assertSame(100, $audioResult->discountValue);
        $this->assertSame(0, $audioResult->distributedOrderDiscountValue);
        $this->assertSame(400, $audioResult->discountedUnitPrice);
        $this->assertSame(400, $audioResult->total);
        $this->assertSame(92, $audioResult->taxValue);
    }

    public function test_calculates_two_products_with_order_promotion(): void
    {
        $order = $this->createOrder();

        $bookProduct = $this->createProduct(
            id: 1,
            price: 1000,
            type: Product::TYPE_BOOK,
            taxRate: 23,
        );

        $audioProduct = $this->createProduct(
            id: 2,
            price: 500,
            type: Product::TYPE_AUDIO,
            taxRate: 23,
        );

        $bookItem = OrderItem::createForProduct($bookProduct, Quantity::fromInt(1));
        $audioItem = OrderItem::createForProduct($audioProduct, Quantity::fromInt(1));

        $order->addItem($bookItem);
        $order->addItem($audioItem);

        $orderPromotion = $this->createPromotion(
            id: 10,
            type: Promotion::TYPE_ORDER,
            percentageDiscount: 20,
        );

        $order->getOrderPromotions()->add(OrderPromotion::create($order, $orderPromotion));

        $bookResult = $this->calculator->calculate($bookItem, $order);
        $audioResult = $this->calculator->calculate($audioItem, $order);

        // Order subtotal = 1000 + 500 = 1500
        // Order discount = 20% of 1500 = 300

        // BOOK share: 1000 / 1500 * 300 = 200 -> 1000 - 200 = 800
        $this->assertNull($bookResult->discount);
        $this->assertSame(0, $bookResult->discountValue);
        $this->assertSame(200, $bookResult->distributedOrderDiscountValue);
        $this->assertSame(800, $bookResult->discountedUnitPrice);
        $this->assertSame(800, $bookResult->total);
        $this->assertSame(184, $bookResult->taxValue);

        // AUDIO share: 500 / 1500 * 300 = 100 -> 500 - 100 = 400
        $this->assertNull($audioResult->discount);
        $this->assertSame(0, $audioResult->discountValue);
        $this->assertSame(100, $audioResult->distributedOrderDiscountValue);
        $this->assertSame(400, $audioResult->discountedUnitPrice);
        $this->assertSame(400, $audioResult->total);
        $this->assertSame(92, $audioResult->taxValue);
    }

    public function test_calculates_three_products_with_item_and_order_promotions(): void
    {
        $order = $this->createOrder();

        $firstBookProduct = $this->createProduct(
            id: 1,
            price: 1000,
            type: Product::TYPE_BOOK,
            taxRate: 23,
        );

        $secondBookProduct = $this->createProduct(
            id: 2,
            price: 500,
            type: Product::TYPE_BOOK,
            taxRate: 23,
        );

        $audioProduct = $this->createProduct(
            id: 3,
            price: 250,
            type: Product::TYPE_AUDIO,
            taxRate: 23,
        );

        $firstBookItem = OrderItem::createForProduct($firstBookProduct, Quantity::fromInt(1));
        $secondBookItem = OrderItem::createForProduct($secondBookProduct, Quantity::fromInt(1));
        $audioItem = OrderItem::createForProduct($audioProduct, Quantity::fromInt(1));

        $order->addItem($firstBookItem);
        $order->addItem($secondBookItem);
        $order->addItem($audioItem);

        $itemPromotion = $this->createPromotion(
            id: 1,
            type: Promotion::TYPE_ITEM,
            percentageDiscount: 10,
            productTypesFilter: [Product::TYPE_BOOK],
        );

        $orderPromotion = $this->createPromotion(
            id: 2,
            type: Promotion::TYPE_ORDER,
            percentageDiscount: 20,
        );

        $order->getOrderPromotions()->add(OrderPromotion::create($order, $itemPromotion));
        $order->getOrderPromotions()->add(OrderPromotion::create($order, $orderPromotion));

        $firstBookResult = $this->calculator->calculate($firstBookItem, $order);
        $secondBookResult = $this->calculator->calculate($secondBookItem, $order);
        $audioResult = $this->calculator->calculate($audioItem, $order);

        // After item promotion:
        // 1000 -> 900
        // 500 -> 450
        // 250 -> 250
        // subtotal = 1600
        // order discount = 320

        // First book: 320 * 900 / 1600 = 180 -> 900 - 180 = 720
        $this->assertSame(10, $firstBookResult->discount);
        $this->assertSame(100, $firstBookResult->discountValue);
        $this->assertSame(180, $firstBookResult->distributedOrderDiscountValue);
        $this->assertSame(720, $firstBookResult->discountedUnitPrice);
        $this->assertSame(720, $firstBookResult->total);
        $this->assertSame(165, $firstBookResult->taxValue);

        // Second book: 320 * 450 / 1600 = 90 -> 450 - 90 = 360
        $this->assertSame(10, $secondBookResult->discount);
        $this->assertSame(50, $secondBookResult->discountValue);
        $this->assertSame(90, $secondBookResult->distributedOrderDiscountValue);
        $this->assertSame(360, $secondBookResult->discountedUnitPrice);
        $this->assertSame(360, $secondBookResult->total);
        $this->assertSame(82, $secondBookResult->taxValue);

        // AUDIO: 320 * 250 / 1600 = 50 -> 250 - 50 = 200
        $this->assertNull($audioResult->discount);
        $this->assertSame(0, $audioResult->discountValue);
        $this->assertSame(50, $audioResult->distributedOrderDiscountValue);
        $this->assertSame(200, $audioResult->discountedUnitPrice);
        $this->assertSame(200, $audioResult->total);
        $this->assertSame(46, $audioResult->taxValue);
    }

    private function createOrder(): Order
    {
        return Order::createCartForUser($this->createUser());
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setName('Test user');

        return $user;
    }

    private function createProduct(
        int $id,
        int $price,
        string $type,
        ?int $taxRate = null,
    ): Product {
        $product = new Product();
        $product->setCode('P' . $id);
        $product->setName('Product ' . $id);
        $product->setType($type);
        $product->setPrice($price);
        $product->setTaxRate($taxRate);

        $this->setEntityId($product, $id);

        return $product;
    }

    private function createPromotion(
        int $id,
        int $type,
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