<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPasswordFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class StaffController extends AbstractController
{
    #[Route('/staff/{username}', name: 'app_staff', methods: ['GET', 'POST'])]
    #[isGranted('IS_AUTHENTICATED')]
    public function index(
        #[CurrentUser]               User $user,
        EntityManagerInterface      $em,
        CategoryRepository          $categoryRepository,
        Request                     $request,
        Security                    $security
    ): Response
    {

        $form = $this->createForm(NewPasswordFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            $this->addFlash('success', 'user.updated_successfully');

            return $security->logout(validateCsrfToken: false) ?? $this->redirectToRoute('app_home');
        }

        return $this->render('staff/index.html.twig', [
            'user' => $user,
            'categories' => $categoryRepository->findAll(),
            'form' => $form->createView(),
            'tokens' => $user->getTokens()
        ]);
    }
}
