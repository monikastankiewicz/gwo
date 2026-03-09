<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Component\Order\Entity\Order;
use App\Component\Order\Entity\OrderItem;
use App\Component\Order\Entity\OrderPromotion;
use App\Component\Order\ValueObject\OrderStatus;
use App\Component\Order\ValueObject\Quantity;
use App\Component\User\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
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
    private PromotionContext $promotionContext;

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
        $this->promotionContext = $environment->getContext(PromotionContext::class);
    }

    /**
     * @When /^user "([^"]+)" adds product "([^"]+)" to the cart$/
     */
    public function userAddsProductToCart(string $userId, string $productId): void
    {
        $payload = [
            'userId' => $this->userContext->getUser($userId)->getId(),
            'productId' => $this->productContext->getProduct($productId)->getId(),
            'quantity' => 1,
        ];

        $this->handleJsonRequest(
            '/api/v1/cart/items',
            Request::METHOD_POST,
            $payload
        );
    }

    /**
     * @When /^user "([^"]*)" adds product "([^"]*)" to the cart twice$/
     */
    public function userAddsProductToTheCartTwice(string $userId, string $productId): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->userAddsProductToCart($userId, $productId);
        }
    }

    /**
     * @Given /^there exists a cart for user "([^"]*)" with items:$/
     */
    public function thereExistsACartForUserWithItems(string $userId, TableNode $table): void
    {
        $user = $this->userContext->getUser($userId);

        $order = Order::createCartForUser($user);

        foreach ($table as $row) {
            $product = $this->productContext->getProduct($row['productId']);
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
        $user = $this->userContext->getUser($userId);
        $product = $this->productContext->getProduct($productId);
        $order = $this->getCartForUserOrFail($user);

        foreach ($order->getItems() as $item) {
            if ($item->getProduct()?->getId() === (int) $product->getId()) {
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
        $user = $this->userContext->getUser($userId);
        $order = $this->getCartForUserOrFail($user);
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
        $order = $this->getOrder($orderId);

        foreach ($table as $row) {
            $promotion = $this->promotionContext->getPromotion($row['promotionId']);
            $order->getOrderPromotions()->add(OrderPromotion::create($order, $promotion));
        }

        $this->em->flush();
    }

    /**
     * @When /^user opens preview of cart "([^"]*)"$/
     */
    public function userOpensPreviewOfCart(string $cartId): void
    {
        $order = $this->getOrder($cartId);

        $this->handleRequest(
            Request::create(sprintf('/api/v1/cart/%s', $order->getId()))
        );
    }

    /**
     * @Given /^there exists a cart with id "([^"]*)" for user "([^"]*)" containing items:$/
     */
    public function thereExistsACartWithIdForUserContainingItems(string $cartId, string $userId, TableNode $table): void
    {
        $user = $this->userContext->getUser($userId);

        $order = Order::createCartForUser($user);
        $this->orders[$cartId] = $order;
        $this->em->persist($order);

        foreach ($table as $row) {
            $product = $this->productContext->getProduct($row['productId']);

            $item = OrderItem::createForProduct($product, Quantity::fromInt((int) $row['quantity']));
            $order->addItem($item);
            $this->em->persist($item);
        }

        $this->em->flush();
    }

    /**
     * @When /^user "([^"]*)" adds promotion "([^"]*)" to the cart "([^"]*)"$/
     */
    public function userAddsPromotionToTheCart(string $userId, string $promotionId, string $cartId): void
    {
        $user = $this->userContext->getUser($userId);

        if ($user === null) {
            throw new \RuntimeException(sprintf('User "%s" not found.', $userId));
        }

        $promotion = $this->promotionContext->getPromotion($promotionId);

        $payload = [
            'userId' => $user->getId(),
            'promotionId' => $promotion->getId(),
        ];

        $card = $this->getOrder($cartId);

        $this->handleJsonRequest(
            sprintf('/api/v1/cart/%s/promotions', $card->getId()),
            Request::METHOD_POST,
            $payload
        );
    }

    private function getCartForUserOrFail(User $user): Order
    {
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

    private function getOrder(string $id): ?Order
    {
        return $this->orders[$id] ?? null;
    }
}