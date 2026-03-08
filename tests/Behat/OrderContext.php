<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Component\Order\Entity\Order;
use App\Component\Order\Entity\OrderItem;
use App\Component\Order\Entity\OrderPromotion;
use App\Component\Order\ValueObject\OrderStatus;
use App\Component\Order\ValueObject\Quantity;
use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use App\Component\User\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class OrderContext implements Context
{
    /** @var array<string, Order> */
    protected array $orders = [];

    private ProductContext $productContext;
    private UserContext $userContext;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SharedStorage $sharedStorage,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->productContext = $environment->getContext(ProductContext::class);
        $this->userContext = $environment->getContext(UserContext::class);
    }

    /**
     * @When /^user "([^"]+)" adds product "([^"]+)" to the cart$/
     */
    public function userAddsProductToCart(string $userId, string $productId, PyStringNode $body): void
    {
        $payload = json_decode($body->getRaw(), true);

        $this->handleJsonRequest(
            '/api/v1/orders/cart/items',
            Request::METHOD_POST,
            $payload
        );
    }

    /**
     * @When /^user "([^"]*)" adds product "([^"]*)" to the cart twice$/
     */
    public function userAddsProductToTheCartTwice(string $userId, string $productId, PyStringNode $body): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->userAddsProductToCart($userId, $productId, $body);
        }
    }

    /**
     * @Given /^there exists a cart for user "([^"]*)" with items:$/
     */
    public function thereExistsACartForUserWithItems(string $userId, TableNode $table): void
    {
        $user = $this->getUserOrFail($userId);
        $order = Order::createCartForUser($user);

        foreach ($table as $row) {
            $product = $this->getProductOrFail($row['productId']);
            $order->addProduct($product, Quantity::fromInt((int) $row['quantity']));
        }

        $this->persistAndFlush($order);
    }

    /**
     * @Then the product :productId in user :userId cart should still have quantity :expectedQuantity
     */
    public function theProductInUserCartShouldStillHaveQuantity(
        string $productId,
        string $userId,
        int $expectedQuantity
    ): void {
        $order = $this->getCartForUserOrFail($userId);

        foreach ($order->getItems() as $item) {
            if ($item->getProduct()?->getId() === (int) $productId) {
                if ($item->getQuantity() === $expectedQuantity) {
                    return;
                }

                throw new \RuntimeException(sprintf(
                    'Expected quantity %d, got %d.',
                    $expectedQuantity,
                    $item->getQuantity()
                ));
            }
        }

        throw new \RuntimeException(sprintf('Product %s not found in cart.', $productId));
    }

    /**
     * @Then the user :userId cart should still contain :expectedCount distinct products
     */
    public function theUserCartShouldStillContainDistinctProducts(string $userId, int $expectedCount): void
    {
        $order = $this->getCartForUserOrFail($userId);
        $actualCount = $order->getItems()->count();

        if ($actualCount !== $expectedCount) {
            throw new \RuntimeException(sprintf(
                'Expected cart to contain %d distinct products, got %d.',
                $expectedCount,
                $actualCount
            ));
        }
    }

    /**
     * @Given /^cart "([^"]*)" has following promotions assigned:$/
     */
    public function cartHasFollowingPromotionsAssigned(string $orderId, TableNode $table): void
    {
        $order = $this->getOrderOrFail($orderId);

        foreach ($table as $row) {
            $promotion = $this->getPromotionOrFail($row['promotionId']);
            $order->getOrderPromotions()->add(OrderPromotion::create($order, $promotion));
        }

        $this->em->flush();
    }

    /**
     * @When /^user opens preview of cart "([^"]*)"$/
     */
    public function userOpensPreviewOfCart(string $cartId): void
    {
        $order = $this->getOrderOrFail($cartId);

        $this->handleRequest(
            Request::create(sprintf('/api/v1/orders/%s', $order->getId()))
        );
    }

    /**
     * @Given /^there exists a cart with id "([^"]*)" for user "([^"]*)" containing items:$/
     */
    public function thereExistsACartWithIdForUserContainingItems(string $cartId, string $userId, TableNode $table): void
    {
        $user = $this->getUserOrFail($userId);

        $order = Order::createCartForUser($user);
        $this->orders[$cartId] = $order;
        $this->em->persist($order);

        foreach ($table as $row) {
            $product = $this->getProductOrFail($row['productId']);

            $item = OrderItem::createForProduct($product, Quantity::fromInt((int) $row['quantity']));
            $order->addItem($item);
            $this->em->persist($item);
        }

        $this->em->flush();
    }

    public function getOrder(string $id): ?Order
    {
        return $this->orders[$id] ?? null;
    }

    private function getOrderOrFail(string $orderId): Order
    {
        $order = $this->getOrder($orderId);

        if ($order === null) {
            throw new \RuntimeException(sprintf('Order "%s" not found.', $orderId));
        }

        return $order;
    }

    private function getUserOrFail(string $userId): User
    {
        $user = $this->userContext->getUser($userId);

        if ($user === null) {
            throw new \RuntimeException(sprintf('User "%s" not found.', $userId));
        }

        return $user;
    }

    private function getProductOrFail(string $productId): Product
    {
        $product = $this->productContext->getProduct($productId);

        if ($product === null) {
            throw new \RuntimeException(sprintf('Product "%s" not found.', $productId));
        }

        return $product;
    }

    private function getPromotionOrFail(string $promotionId): Promotion
    {
        $promotion = $this->em->getRepository(Promotion::class)->find((int) $promotionId);

        if ($promotion === null) {
            throw new \RuntimeException(sprintf('Promotion "%s" not found.', $promotionId));
        }

        return $promotion;
    }

    private function getCartForUserOrFail(string $userId): Order
    {
        $user = $this->em->getRepository(User::class)->find((int) $userId);

        if ($user === null) {
            throw new \RuntimeException(sprintf('User "%s" not found.', $userId));
        }

        $order = $this->em->getRepository(Order::class)->findOneBy([
            'user' => $user,
            'status' => OrderStatus::CART,
        ]);

        if ($order === null) {
            throw new \RuntimeException('Cart not found.');
        }

        return $order;
    }

    private function handleJsonRequest(string $uri, string $method, array $payload = []): Response
    {
        $request = Request::create(
            $uri,
            $method,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        return $this->handleRequest($request);
    }

    private function handleRequest(Request $request): Response
    {
        $response = $this->kernel->handle($request);
        $this->sharedStorage->addResponse($response);

        return $response;
    }

    private function persistAndFlush(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }
}