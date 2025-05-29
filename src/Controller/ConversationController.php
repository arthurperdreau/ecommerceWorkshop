<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Profile;
use App\Form\MessageForm;
use App\Repository\ConversationRepository;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sav')]
final class ConversationController extends AbstractController
{

    #[Route('/contact', name: 'app_conversation')]
    public function savHelp(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('conversation/index.html.twig');
    }

    #[Route('/conversations', name: 'app_conversations_sav')]
    public function savInterface(ConversationRepository $conversationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }


        if (!in_array('ROLE_EMPLOYEE', $user->getRoles())) {
            return $this->redirectToRoute('app_product_index');
        }

        $conversations = $conversationRepository->findAll();

        return $this->render('conversation/sav.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/conversation/contactSAV/{id}', name: 'app_conversation_contactSAV')]
    public function contactSAV(ProfileRepository $profileRepository, Profile $profile, ConversationRepository $conversationRepository, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}

        $employee = $profileRepository->findOneByRole('ROLE_EMPLOYEE');


        $conversation = $conversationRepository->find($profile->getId());

        if(!$conversation){
            $conversation = new Conversation();
            $conversation->addParticipant($employee);
            $conversation->addParticipant($profile);
            $manager->persist($conversation);
            $manager->flush();
            $idConversation = $conversation->getId();
        }else{
            $idConversation = $conversation->getId();
        }

        return $this->redirectToRoute('app_conversation_open', [
            "id"=>$idConversation,
        ]);
    }

    #[Route('/conversation/open/{id}', name: 'app_conversation_open')]
    public function openChat(Conversation $conversation,Request $request,EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}
        if(!$conversation){return $this->redirectToRoute('app_conversation');}

        $message = new Message();
        $form = $this->createForm(MessageForm::class, $message);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $message->setCreatedAt((new \DateTimeImmutable()));
            $message->setAuthor($this->getUser()->getProfile());
            $message->setConversation($conversation);
            $manager->persist($message);
            $manager->flush();
            return $this->redirectToRoute('app_conversation_open',["id"=>$conversation->getId()]);
        }

        return $this->render('conversation/open.html.twig', [
            'conversation' => $conversation,
            'form' => $form,
        ]);

    }
}
