<?php

namespace App\Controller;

use App\Entity\Relevance;
use App\Entity\Review;
use App\Form\RelevanceForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RelevanceController extends AbstractController
{
    #[Route('/relevance/new/{id}', name: 'app_relevance_new')]
    public function new(Review $review, EntityManagerInterface $manager, Request $request): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }

        $relevance = new Relevance();
        $form=$this->createForm(RelevanceForm::class , $relevance);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $relevance->setReview($review);
            $relevance->setAuthor($this->getUser()->getProfile());
            $manager->persist($relevance);
            $manager->flush();
            return $this->redirectToRoute('app_product_show', ['id' => $review->getProduct()->getId()]);
        }


        return $this->render('relevance/index.html.twig', [
            'controller_name' => 'RelevanceController',
            'form' => $form,
        ]);
    }
}
