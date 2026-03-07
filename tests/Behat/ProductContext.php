<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Component\Product\Entity\Product;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class ProductContext implements Context
{
    /** @var array<string, Product> */
    protected array $products = [];

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RouterInterface $router,
        private readonly SharedStorage $sharedStorage,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @When a user sends request to get all products
     */
    public function aUserSendsRequestToGetAllProducts(): void
    {
        $path = $this->router->generate('productList');
        $response = $this->kernel->handle(Request::create($path));
        $this->sharedStorage->addResponse($response);
    }

    /**
     * @Given there should be :numberOfProducts products in response
     */
    public function thereShouldBeProductsInResponse(int $numberOfProducts): void
    {
        if (empty($this->sharedStorage)) {
            throw new \RuntimeException('No response received');
        }

        /** @var array{data: mixed} $response */
        $response = json_decode($this->sharedStorage->getLastResponse()->getContent() ?: '', true, JSON_THROW_ON_ERROR);
        Assert::isArray($response['data']);

        Assert::count($response['data'], $numberOfProducts);
    }

    public function getProduct(string $id): ?Product
    {
        return $this->products[$id] ?? null;
    }

    /**
     * @Given /^there exist following products:$/
     */
    public function thereExistFollowingProducts(TableNode $table): void
    {
        foreach ($table as $row) {
            $product = $this->getProduct($row['id']);

            if (!$product) {
                $product = new Product();
                $product->setCode($row['code']);
                $product->setName($row['name']);
                $product->setPrice((int)$row['price']);
                $product->setType($row['type']);
                $product->setTaxRate((int)$row['taxRate']);

                $this->em->persist($product);

                $this->products[$row['id']] = $product;
            }
        }

        $this->em->flush();
    }
}
