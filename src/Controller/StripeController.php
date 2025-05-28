<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\AddressRepository;
use App\Service\CartService;
use App\Service\JWTService;
use App\Service\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeController extends AbstractController
{
    #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(
        Request $request,
        CartService $cartService,
        AddressRepository $addressRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        JWTService $jwt, SendEmailService $mail,
    ): JsonResponse {
        // On parse le body JSON
        $data = json_decode($request->getContent(), true);
        $billingId = $data['billingId'] ?? null;
        $shippingId = $data['shippingId'] ?? null;

        if (!$billingId || !$shippingId) {
            return new JsonResponse(['error' => 'Missing addresses'], 400);
        }

        $billing = $addressRepository->find($billingId);
        $shipping = $addressRepository->find($shippingId);
        if (!$billing || !$shipping) {
            return new JsonResponse(['error' => 'Address not found'], 404);
        }

        // Crée la commande (Order)
        $order = new Order();
        $order->setCustomer($this->getUser());
        $order->setBillingAddress($billing);
        $order->setShippingAddress($shipping);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatus(0); // En attente de paiement
        $order->setTotal($cartService->getTotal());
        $entityManager->persist($order);

        // Stripe line items
        $lineItems = [];

        foreach ($cartService->getCart() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem['product']);
            $orderItem->setQuantity($cartItem['quantity']);
            $orderItem->setCreatedAt(new \DateTimeImmutable());
            $orderItem->setOfOrder($order);
            $orderItem->setPrepared(false);
            $entityManager->persist($orderItem);
            $cartItem["product"]->setStock($cartItem["product"]->getStock() - $cartItem["quantity"]);
            $entityManager->persist($cartItem["product"]);

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $cartItem['product']->getPrice() * 100,
                    'product_data' => [
                        'name' => $cartItem['product']->getName(),
                    ],
                ],
                'quantity' => $cartItem['quantity'],
            ];
            $entityManager->flush();
            $order->addOrderItem($orderItem);
            $entityManager->flush();
        }


        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // Payload
        $payload = [
            'user_id' => $this->getUser()->getId()
        ];

        // On génère le token
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        $user=$this->getUser();
        $mail->send(
            'contact@arthurperdreau.com',
            $this->getUser()->getEmail(),
            'Order validated',
            'orderValidated',
            compact('user', 'token', 'order')
        );

        // Clé secrète Stripe
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // Création de la session Stripe Checkout
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate('payment_success', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $urlGenerator->generate('payment_cancel', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $session->id]);
    }

    #[Route('/payment/success/{id}', name: 'payment_success')]
    public function paymentSuccess(Order $order, CartService $cartService, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $order->setStatus(1); // Paiement validé
        $em->flush();

        // Vider le panier
        $cartService->emptyCart();

        return $this->redirectToRoute('app_my_orders', ['id' => $order->getId()]);
    }

    #[Route('/payment/cancel/{id}', name: 'payment_cancel')]
    public function paymentCancel(Order $order): \Symfony\Component\HttpFoundation\Response
    {
        // Ici, tu peux décider de supprimer ou garder la commande en attente
        return $this->render('payment/cancel.html.twig', [
            'order' => $order
        ]);
    }
}
