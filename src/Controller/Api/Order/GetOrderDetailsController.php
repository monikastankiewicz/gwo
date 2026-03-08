<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Service\Order\GetOrderDetailsHandler;
use App\Service\Order\View\OrderDetailsView;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class GetOrderDetailsController extends AbstractController
{
    #[Route('/orders/{orderId}', name: 'api_order_details', methods: ['GET'])]
    #[OA\Tag(name: 'Order')]
    #[OA\Parameter(
        name: 'orderId',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Order details',
        content: new OA\JsonContent(
            ref: new Model(type: OrderDetailsView::class)
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
        int $orderId,
        GetOrderDetailsHandler $handler,
        SerializerInterface $serializer,
    ): JsonResponse {
        $view = $handler->handle($orderId);

        $data = $serializer->serialize($view, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}