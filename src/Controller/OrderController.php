<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly SerializerInterface $serializer,
        private readonly ProductRepository $productRepository,
    )
    {
    }

    #[Route(path: 'api/orders/{id}', name: 'api_get_order_by_id', methods: ['GET'])]
    public function getOrderByIdAction(int $id): Response
    {
        $order = $this->orderRepository->findOneBy(['id' => $id]);
        $data = $this->serializer->serialize($order, 'json', ['groups' => 'get']);

        return new JsonResponse($data, Response::HTTP_OK, json: true);
    }

    #[Route(path: 'api/orders', name: 'api_create_order', methods: ['POST'])]
    public function createOrderAction(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $order = new Order();
        $order->setOrderDate(new \DateTime());

        try {
            foreach ($data['orderItems'] as $item) {
                $orderItem = new OrderItem();
                $orderItem
                    ->setProduct(
                        $this->productRepository->findOneBy(['id' => $item['productId']])
                    )
                    ->setQuantity($item['quantity']);
                $orderItem->setOrder($order);

                $order->addOrderItem($orderItem);
            }

            $this->orderRepository->add($order);
        } catch (\Exception $e) {
            return new JsonResponse([$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        $order = $this->orderRepository->findOneBy(['id' => $order->getId()]);
        $data = $this->serializer->serialize($order, 'json', ['groups' => 'get']);

        return new JsonResponse($data, Response::HTTP_OK, json: true);
    }
}
