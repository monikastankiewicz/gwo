<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Component\Order\Entity\Order;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use App\Service\Order\AddPromotionToCart;
use App\Service\Order\AddPromotionToCartHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class AddPromotionToCartController extends AbstractController
{
    #[Route('/cart/{cartId}/promotions', name: 'api_cart_add_promotion', methods: ['POST'])]
    #[OA\Tag(name: 'Cart')]
    #[OA\Parameter(
        name: 'cartId',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: new Model(type: AddPromotionToCart::class)
        )
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Promotion added to order successfully',
        content: new OA\JsonContent(
            ref: new Model(type: Order::class, groups: ['order:read'])
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Validation failed or promotion business rule was violated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 422),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'A promotion of this type has already been applied to the order.'
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'Cart or promotion not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 404),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Cart not found.'
                ),
            ],
            type: 'object'
        )
    )]
    public function __invoke(Request $request, int $cartId, AddPromotionToCartHandler $handler, SerializerInterface $serializer): JsonResponse
    {
        $command = $serializer->deserialize(
            $request->getContent(),
            AddPromotionToCart::class,
            'json'
        );

        $handler->handle($cartId, $command);

        return new JsonResponse(['message' => 'Promotion assigned to cart successfully.'], Response::HTTP_OK);
    }
}