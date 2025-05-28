<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\JWTService;
use App\Service\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderController extends AbstractController
{
    #[Route('/order/addresses', name: 'app_order_addresses')]
    public function chooseAddresses(AddressRepository $addressRepository): Response
    {
        $profile = $this->getUser()->getProfile();
        $addresses = $addressRepository->findBy(['profile' => $profile]);

        return $this->render('order/addresses.html.twig', [
            'addresses' => $addresses
        ]);
    }

    #[Route('/order/billingaddress', name: 'app_order_billingaddress')]
    public function billingAddress(): Response
    {

        return $this->render('order/billingAddress.html.twig');
    }
    #[Route('/order/shippingaddress/{id}', name: 'app_order_shippingaddress')]
    public function shippingAddress(Address $address): Response
    {

        return $this->render('order/shippingAddress.html.twig',[
            'billingAddress' => $address
        ]);
    }
    #[Route('/order/payment/{idBilling}/{idShipping}', name: 'app_order_payment')]
    public function payment(CartService $cartService, AddressRepository $addressRepository, $idBilling, $idShipping,): Response
    {
        $billing = $addressRepository->find($idBilling);
        $shipping = $addressRepository->find($idShipping);

        return $this->render('order/payment.html.twig', [
            'billing' => $billing,
            'shipping' => $shipping,
            'cart' => $cartService->getCart(),
            'total' => $cartService->getTotal(),
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
        ]);
    }

    #[Route('/order/validate/{idBilling}/{idShipping}', name: 'app_order_validate')]
    public function validate(EntityManagerInterface $manager, AddressRepository $addressRepository, $idBilling, $idShipping, CartService $cartService, \Knp\Snappy\Pdf $knpSnappyPdf): Response
    {

        $billing = $addressRepository->find($idBilling);
        $shipping = $addressRepository->find($idShipping);

        $order = new Order();
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatus(1);
        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);
        $order->setCustomer($this->getUser());
        $order->setTotal($cartService->getTotal());
        $manager->persist($order);


        foreach ($cartService->getCart() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setCreatedAt(new \DateTimeImmutable());
            $orderItem->setProduct($cartItem["product"]);
            $orderItem->setQuantity($cartItem["quantity"]);
            $orderItem->setOfOrder($order);
            $orderItem->setPrepared(false);
            $manager->persist($orderItem);
            $cartItem["product"]->setStock($cartItem["product"]->getStock() - $cartItem["quantity"]);
            $manager->persist($cartItem["product"]);
        }
        $manager->flush();
        $cartService->emptyCart();

        return $this->redirectToRoute("app_my_orders");
    }
    #[Route('/myorders', name: 'app_my_orders')]
    public function myOrders(OrderRepository $orderRepository): Response
    {
        return $this->render('order/myorders.html.twig',[
            'orders' => $orderRepository->findBy(['customer' => $this->getUser()]),
            ]
        );
    }

    #[Route('/product/pdf/{id}', name: 'app_product_pdf')]
    public function productPdf(Pdf $knpSnappyPdf, Order $order): Response
    {
        $html = $this->renderView('order/pdf.html.twig', [
            'order' => $order
        ]);

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            'commande_' . $order->getId() . '.pdf'
        );
    }

    #[Route('/order/see/{id}', name: 'app_order_see')]
    public function seeOrder(Order $order): Response
    {
        return $this->render('order/seeOrder.html.twig',[
            'order' => $order
        ]);
    }

    #[Route('/order-item/{id}/prepare', name: 'order_item_prepare')]
    public function prepare(OrderItem $orderItem, EntityManagerInterface $manager): Response
    {
        $orderItem->setPrepared(true);
        $manager->flush();

        return $this->redirectToRoute('app_order_see', ['id' => $orderItem->getOfOrder()->getId()]);
    }

    #[Route('/order/{id}/validate-preparation', name: 'order_validate_preparation')]
    public function validatePreparation(Order $order, EntityManagerInterface $manager): Response
    {
        foreach ($order->getOrderItems() as $item) {
            if (!$item->isPrepared()) {;
                return $this->redirectToRoute('app_order_see', ['id' => $order->getId()]);
            }
        }

        $order->setStatus(2);
        $manager->flush();

        return $this->redirectToRoute('app_order_see', ['id' => $order->getId()]);
    }

    #[Route('/order/{id}/cancel', name: 'app_order_cancel')]
    public function cancelOrder(Order $order, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles()) || !in_array("ROLE_EMPLOYEE", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        if ($order->getStatus() !== 1) {
            return $this->redirectToRoute('app_order_see', ['id' => $order->getId()]);
        }

        foreach ($order->getOrderItems() as $item) {
            $product = $item->getProduct();
            $product->setStock($product->getStock() + $item->getQuantity());
            $manager->persist($product);
            $manager->remove($item);
        }

        $manager->remove($order);

        $manager->flush();
        return $this->redirectToRoute('app_product_index');
    }




}
