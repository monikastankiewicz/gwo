<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Component\Order\Entity\Order;
use App\Component\Order\Exception\OrderNotFound;
use App\Service\Order\View\OrderDetailsView;
use App\Service\Order\View\OrderDetailsViewFactory;
use Doctrine\Persistence\ManagerRegistry;

final class GetOrderDetailsHandler
{
    public function __construct(
        private readonly ManagerRegistry         $doctrine,
        private readonly OrderDetailsViewFactory $orderDetailsViewFactory,
    ) {
    }

    public function handle(int $orderId): OrderDetailsView
    {
        $order = $this->doctrine->getRepository(Order::class)->find($orderId);

        if (!$order instanceof Order) {
            throw new OrderNotFound();
        }

        return $this->orderDetailsViewFactory->create($order);
    }
}