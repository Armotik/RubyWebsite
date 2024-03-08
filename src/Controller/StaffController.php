<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StaffController extends AbstractController
{
    #[Route('/staff/{username}', name: 'app_staff')]
    public function index(string $username, UserRepository $userRepository, CategoryRepository $categoryRepository): Response
    {

        if (!$this->getUser()) {
            return $this->redirectToRoute("app_login");
        }

        return $this->render('staff/index.html.twig', [
            'user' => $userRepository->findOneBy(['username'=>$username]),
            'categories' => $categoryRepository->findAll()
        ]);
    }
}
