<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Component\Order\Entity\Order;
use App\Component\Order\ValueObject\OrderStatus;
use App\Component\Order\ValueObject\Quantity;
use App\Component\User\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class OrderContext implements Context
{
    private ProductContext $productContext;
    private UserContext $userContext;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SharedStorage          $sharedStorage,
        private readonly KernelInterface        $kernel,
    )
    {
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

        $request = Request::create(
            '/api/v1/orders/cart/items',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->kernel->handle($request);
        $this->sharedStorage->addResponse($response);
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
        $user = $this->userContext->getUser($userId);

        if ($user === null) {
            throw new \RuntimeException(sprintf('User "%s" not found.', $userId));
        }

        $order = Order::createCartForUser($user);

        foreach ($table as $row) {
            $product = $this->productContext->getProduct($row['productId']);

            if ($product === null) {
                throw new \RuntimeException(sprintf('Product "%s" not found.', $row['productId']));
            }

            $order->addProduct($product, Quantity::fromInt((int)$row['quantity']));
        }

        $this->em->persist($order);
        $this->em->flush();
    }

    /**
     * @Then the product :productId in user :userId cart should still have quantity :expectedQuantity
     */
    public function theProductInUserCartShouldStillHaveQuantity(
        string $productId,
        string $userId,
        int    $expectedQuantity
    ): void
    {
        $order = $this->em
            ->getRepository(Order::class)
            ->findOneBy([
                'user'   => $this->em->getRepository(User::class)->find((int)$userId),
                'status' => OrderStatus::CART,
            ]);

        if ($order === null) {
            throw new \RuntimeException('Cart not found.');
        }

        foreach ($order->getItems() as $item) {
            if ($item->getProduct()?->getId() === (int)$productId) {
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
        $user = $this->em->getRepository(User::class)->find((int)$userId);

        if ($user === null) {
            throw new \RuntimeException(sprintf('User "%s" not found.', $userId));
        }

        $order = $this->em->getRepository(Order::class)->findOneBy([
            'user'   => $user,
            'status' => OrderStatus::CART,
        ]);

        if ($order === null) {
            throw new \RuntimeException('Cart not found.');
        }

        $actualCount = $order->getItems()->count();

        if ($actualCount !== $expectedCount) {
            throw new \RuntimeException(sprintf(
                'Expected cart to contain %d distinct products, got %d.',
                $expectedCount,
                $actualCount
            ));
        }
    }
}
