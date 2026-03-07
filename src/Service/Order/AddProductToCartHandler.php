<?php
declare(strict_types=1);

namespace App\Service\Order;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\CartProvider;
use App\Component\Order\ValueObject\Quantity;
use App\Component\Product\Entity\Product;
use App\Component\Product\Exception\ProductNotFound;
use App\Component\User\Entity\User;
use App\Component\User\Exception\UserNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

class AddProductToCartHandler
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly CartProvider $cartProvider,
        private readonly EntityManagerInterface $em,
    ) {}

    public function handle(AddProductToCart $cmd): Order
    {
        $user = $this->doctrine->getRepository(User::class)->find($cmd->userId);
        if (!$user instanceof User) {
            throw new UserNotFound();
        }

        $product = $this->doctrine->getRepository(Product::class)->find($cmd->productId);
        if (!$product instanceof Product) {
            throw new ProductNotFound();
        }

        $cart = $this->cartProvider->getOrCreateCartForUser($user);
        $cart->addProduct($product, Quantity::fromInt($cmd->quantity));
        $this->em->flush();

        return $cart;
    }
}