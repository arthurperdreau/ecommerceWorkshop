<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartService $cartService): Response
    {

        return $this->render('cart/index.html.twig', [
            'cart' => $cartService->getCart(),
            'total' => $cartService->getTotal(),
        ]);
    }
    #[Route('/cart/add/{id}/{quantity}', name: 'app_cart_add_product')]
    public function add(Product $product, int $quantity, CartService $cartService): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        if(!$product){return $this->redirectToRoute('app_product_index');}
        $cartService->addToCart($product, $quantity);
        return $this->redirectToRoute('app_cart');


    }

    #[Route('/cart/removeunit/{id}/{quantity}', name: 'app_cart_remove_unit')]
    public function remove(Product $product, int $quantity, CartService $cartService): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        if(!$product){return $this->redirectToRoute('app_product_index');}
        $cartService->removeOneUnitFromCart($product, $quantity);
        return $this->redirectToRoute('app_cart');

    }

    #[Route('/cart/removeitem/{id}', name: 'app_cart_remove_item')]
    public function removeItem(Product $product, CartService $cartService): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        if(!$product){return $this->redirectToRoute('app_product_index');}
        $cartService->removeProductFromCart($product);
        return $this->redirectToRoute('app_cart');
    }
}
