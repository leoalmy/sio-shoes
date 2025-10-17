<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\OrderFormType;
use App\Repository\ProductRepository;
use App\Repository\CityRepository;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TranspcrtExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer){}

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
    ): Response {
        $cart = $session->get('cart', []);
        $cartWithData = [];

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);

            if ($product) {
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
            }
        }

        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWithData));

        $order = new Order();
        $form = $this->createForm(OrderFormType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($cart)) {
                $order->setCreatedAt(new \DateTimeImmutable());
                $order->setTotalPrice($total + $order->getShippingPrice());

                $entityManager->persist($order);
                $entityManager->flush();

                foreach ($cart as $id => $quantity) {
                    $product = $productRepository->find($id);
                    if ($product) {
                        $orderProduct = new OrderProducts();
                        $orderProduct->setOrder($order);
                        $orderProduct->setProduct($product);
                        $orderProduct->setQte($quantity);
                        $orderProduct->setPrice($product->getPrice());
                        $order->addOrderProduct($orderProduct);
                        $entityManager->persist($orderProduct);
                        $entityManager->flush();
                    }
                }

                $session->remove('cart');

                $html = $this->renderView('mail/order.html.twig', [
                    'order'=>$order
                ]);

                $email = (new Email())
                ->from('no-reply@sio-shoes.fr')
                ->to('leo.almy@proton.me')
                ->subject('Confirmation de commande Sio-Shoes')
                ->html($html);

                $this->mailer->send($email);

                return $this->redirectToRoute('order_message', [], Response::HTTP_SEE_OTHER);
            }
            else {
                    $this->addFlash('error', 'Votre panier est vide.');
                    return $this->redirectToRoute('app_cart', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
            'form' => $form,
            'total' => $total,
        ]);
    }

    #[Route('/get_shipping_cost', name: 'get_shipping_cost', methods: ['POST'])]
    public function getShippingCost(Request $request, CityRepository $cityRepository): JsonResponse
    {
        $id = $request->request->get('city');
        $city = $cityRepository->find($id);
        $cost = $city->getShippingCost() ?? 10.00;

        return new JsonResponse(['shippingCost' => $cost]);
    }

    #[Route('/order-message', name: 'order_message')]
    public function orderMessage(): Response
    {
        return $this->render('order/order-message.html.twig');
    }

    #[Route('/editor/orders', name: 'editor_orders')]
    public function getAllOrders(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();

        return $this->render('order/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/editor/order/delete/{id}', name: 'editor_order_delete', methods: ['POST', 'GET'])]
    public function deleteOrder(
        Order $order,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        // Protection CSRF (si tu utilises un formulaire DELETE)
        if ($this->isCsrfTokenValid('delete_order_' . $order->getId(), $request->get('_token'))) {
            $em->remove($order);
            $em->flush();

            $this->addFlash('success', 'Commande supprimée avec succès.');
        }

        return $this->redirectToRoute('editor_orders');
    }

    #[Route('/editor/order/{id}/is-delivered/update', name: 'editor_order_is_delivered_update')]
    public function updateIsDelivered(
        OrderRepository $orderRepository,
        EntityManagerInterface $em,
        int $id
    ): Response {
        $order = $orderRepository->find($id);

        $order->setIsDelivered(true);
        $em->persist($order);
        $em->flush();
        
        $this->addFlash('success', 'La commande a été marquée comme livrée.');

        return $this->redirectToRoute('editor_orders');
    }
}
