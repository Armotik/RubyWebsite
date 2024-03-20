<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        CategoryRepository  $categoryRepository
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_staff', ['username' => $this->getUser()->getUserIdentifier()]);
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'categories' => $categoryRepository->findAll()
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
