<?php

namespace App\Controller;

use App\Form\SearchProductForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NavbarController extends AbstractController
{
    #[Route('/search', name: 'app_navbar')]
    public function index( Request $request,): Response
    {
        $form = $this->createForm(SearchProductForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->get('product')->getData();

            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        return $this->render('navbar/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
