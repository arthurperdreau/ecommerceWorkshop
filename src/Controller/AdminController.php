<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    private function denyAccessUnlessAdmin(): ?Response
    {
        $user = $this->getUser();
        if (!$user || !in_array("ROLE_ADMIN", $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        return null;
    }

    private function addRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        if (!in_array($role, $roles)) {
            array_push($roles, $role);
            $user->setRoles($roles);
        }
    }

    private function removeRole(User $user, string $role): void
    {
        $roles = array_filter($user->getRoles(), fn($r) => $r !== $role);
        $user->setRoles(array_values($roles));
    }

    #[Route('', name: 'app_admin')]
    public function index(UserRepository $userRepository): Response
    {
        if ($response = $this->denyAccessUnlessAdmin()) {
            return $response;
        }

        return $this->render('admin/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/promote/admin/{id}', name: 'promote')]
    public function promoteAdmin(User $user, EntityManagerInterface $manager): Response
    {
        if ($response = $this->denyAccessUnlessAdmin()) {
            return $response;
        }

        $this->addRole($user, 'ROLE_ADMIN');
        $manager->persist($user);
        $manager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/demote/admin/{id}', name: 'demote')]
    public function demoteAdmin(User $user, EntityManagerInterface $manager): Response
    {
        if ($response = $this->denyAccessUnlessAdmin()) {
            return $response;
        }

        $this->removeRole($user, 'ROLE_ADMIN');
        $manager->persist($user);
        $manager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/promote/employee/{id}', name: 'setemployee')]
    public function promoteEmployee(User $user, EntityManagerInterface $manager): Response
    {
        if ($response = $this->denyAccessUnlessAdmin()) {
            return $response;
        }

        $this->addRole($user, 'ROLE_EMPLOYEE');
        $manager->persist($user);
        $manager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/demote/employee/{id}', name: 'demoteemployee')]
    public function demoteEmployee(User $user, EntityManagerInterface $manager): Response
    {
        if ($response = $this->denyAccessUnlessAdmin()) {
            return $response;
        }

        $this->removeRole($user, 'ROLE_EMPLOYEE');
        $manager->persist($user);
        $manager->flush();

        return $this->redirectToRoute('app_admin');
    }


    #[Route('/order/all', name: 'allOrders')]
    public function getAllOrder(OrderRepository $orderRepository): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles()) || !in_array("ROLE_EMPLOYEE", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        return  $this->render('admin/orderAll.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);

    }

    #[Route('/order/inprogress', name: 'orderInProgress')]
    public function orderInProgress(OrderRepository $orderRepository): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles()) || !in_array("ROLE_EMPLOYEE", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        return  $this->render('admin/orderInProgress.html.twig', [
            'orders' => $orderRepository->findBy(['status' => 1]),
        ]);

    }

    #[Route('/stock', name: 'getStock')]
    public function getStock(ProductRepository $productRepository): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles()) || !in_array("ROLE_EMPLOYEE", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('/admin/stock.html.twig', [
            'products' => $productRepository->findAll(),
        ]);

    }

}
