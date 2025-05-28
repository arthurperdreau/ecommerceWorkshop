<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressForm;
use App\Form\ProductForm;
use App\Form\ProfileForm;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index( EntityManagerInterface $manager, Request $request): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }

        $profile=$this->getUser()->getProfile();
        $form=$this->createForm(ProfileForm::class,$profile);
        $form=$form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $manager->persist($profile);
            $manager->flush();
        }

        return $this->render('profile/index.html.twig', [
            'profile' => $this->getUser()->getProfile(),
            'form' => $form,
        ]);
    }

    #[Route('/profile/address/add', name: 'app_address_add')]
    public function addAddress(Request $request,EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }

        $address=new Address();
        $profile=$this->getUser()->getProfile();
        $form=$this->createForm(AddressForm::class,$address);
        $form=$form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $address->setProfile($profile);
            $manager->persist($address);
            $manager->flush();
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/addAddress.html.twig', [
            'form' => $form,
        ]);


    }



}
