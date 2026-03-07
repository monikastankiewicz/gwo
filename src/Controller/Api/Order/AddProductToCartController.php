<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Component\Order\Entity\Order;
use App\Service\Order\AddProductToCart;
use App\Service\Order\AddProductToCartHandler;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class AddProductToCartController extends AbstractController
{
    #[Route('/orders/cart/items', methods: ['POST'])]
    #[OA\Tag(name: 'Cart')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: new Model(type: AddProductToCart::class)
        )
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Product added to cart successfully',
        content: new OA\JsonContent(
            ref: new Model(type: Order::class, groups: ['order:read'])
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Validation failed or cart business rule was violated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 422),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Cart cannot contain more than 10 different products.'
                ),
            ],
            type: 'object'
        )
    )]
    public function __invoke(Request $request, AddProductToCartHandler $handler, SerializerInterface $serializer): JsonResponse
    {
        $command = $serializer->deserialize(data: $request->getContent(), type: AddProductToCart::class, format: 'json');
        $order = $handler->handle($command);

        return $this->json(data: $order, context: ['groups' => ['order:read']]);
    }
}