<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository,
    ){}

    public function getCart():array
    {
        $cart = $this->requestStack->getSession()->get('cart', [] );
        $objectCart = [];
        foreach ($cart as $productId => $quantity){
            $item = [
                "product"=> $this->productRepository->find($productId),
                "quantity" => $quantity,
            ];
            $objectCart[] = $item;
        }
        return $objectCart;
    }

    public function addToCart(Product $product, int $quantity):void
    {
        $cart = $this->requestStack->getSession()->get("cart", []);
        $productId = $product->getId();
        if(isset($cart[$productId])){
            $cart[$productId] += $quantity;

        }else{
            $cart[$productId] = $quantity;

        }
        $this->requestStack->getSession()->set("cart", $cart);
    }

    public function getTotal():float
    {
        $cart=$this->getCart();
        $total = 0;
        foreach ($cart as $item){
            $total += $item["quantity"] * $item["product"]->getPrice();
        }
        return $total;
    }
    public function getCount():int
    {
        $count = 0;

        foreach ($this->requestStack->getSession()->get('cart', []) as $item){
            $count += $item;
        }

        return $count;
    }
    public function removeProductFromCart(Product $product): void
    {
        $cart = $this->requestStack->getSession()->get("cart", []);
        $productId = $product->getId();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $this->requestStack->getSession()->set("cart", $cart);
        }
    }

    public function removeOneUnitFromCart(Product $product, int $quantity): void
    {
        $cart = $this->requestStack->getSession()->get("cart", []);
        $productId = $product->getId();

        if (isset($cart[$productId])) {
            $cart[$productId] -= $quantity;

            if ($cart[$productId] <= 0) {
                unset($cart[$productId]);
            }

            $this->requestStack->getSession()->set("cart", $cart);
        }
    }

    public function emptyCart(){
        $this->requestStack->getSession()->remove("cart");
    }

}