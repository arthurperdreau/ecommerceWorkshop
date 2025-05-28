<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewForm;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/review')]
final class ReviewController extends AbstractController
{
    #[Route('/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if ($this->getUser()->getProfile() !==$review->getAuthor()){
            return $this->redirectToRoute('app_product_show', ['id' => $review->getProduct()->getId()]);
        }
        $form = $this->createForm(ReviewForm::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_product_show', ['id'=>$review->getProduct()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_review_delete')]
    public function delete(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if ($this->getUser()->getProfile() == $review->getAuthor()) {
            foreach ($review->getRelevance() as $relevance) {
                $entityManager->remove($relevance);
            }
            $entityManager->remove($review);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_show', ['id'=>$review->getProduct()->getId()], Response::HTTP_SEE_OTHER);
    }
}
