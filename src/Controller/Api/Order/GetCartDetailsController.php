<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Component\Order\ValueObject\Currency;
use App\Service\Order\GetCartDetailsHandler;
use App\Service\Order\View\CartDetailsView;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class GetCartDetailsController extends AbstractController
{
    #[Route('/cart/{cartId}', name: 'api_order_details', methods: ['GET'])]
    #[OA\Tag(name: 'Cart')]
    #[OA\Get(
        description: 'Returns detailed cart information including item prices, applied promotions, taxes and totals. Supports optional currency conversion (PLN or EUR).',
        summary: 'Get cart details'
    )]
    #[OA\Parameter(
        name: 'cartId',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'currency',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'PLN', enum: ['PLN', 'EUR'])
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Order details',
        content: new OA\JsonContent(
            ref: new Model(type: CartDetailsView::class)
        )
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'Order not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 404),
                new OA\Property(property: 'message', type: 'string', example: 'Order not found.'),
            ],
            type: 'object'
        )
    )]
    public function __invoke(
        int                   $cartId,
        Request               $request,
        GetCartDetailsHandler $handler,
        SerializerInterface   $serializer,
    ): JsonResponse {
        $currency = Currency::fromNullable($request->query->get('currency'));
        $command = $handler->handle($cartId, $currency);
        $data = $serializer->serialize($command, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}