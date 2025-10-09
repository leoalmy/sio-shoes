<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session, CategoryRepository $categoryRepository): Response
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];

        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            }
        }

        $total = 0;
        foreach ($cartWithData as $item) {
            $total += $item['product']->getPrice() * $item['quantity'];
        }

        return $this->render('cart/index.html.twig', [
            'items' => $cartWithData,
            'total' => $total,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['GET'])]
    public function add(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (!array_key_exists($id, $cart)) {
        $cart[$id] = 1;
        } else {
            $cart[$id]++;
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['GET'])]
    public function remove(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (array_key_exists($id, $cart)) {
            unset($cart[$id]);
            $this->addFlash('danger','Le produit a été supprimé du panier.');
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear', methods: ['GET'])]
    public function clear(SessionInterface $session): Response
    {
        $session->remove('cart');
        $this->addFlash('danger','Votre panier a été effacé.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/subtract/{id}', name: 'app_cart_subtract', methods: ['GET'])]
    public function substract(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (array_key_exists($id, $cart)) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
                $this->addFlash('danger','Le produit a été supprimé du panier.');
            }
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }
}
