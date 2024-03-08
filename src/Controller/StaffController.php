<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPasswordFormType;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class StaffController extends AbstractController
{
    #[Route('/staff/{username}', name: 'app_staff')]
    public function index(string $username, UserRepository $userRepository, CategoryRepository $categoryRepository, Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {

        if (!$this->getUser()) {
            return $this->redirectToRoute("app_login");
        }

        $form = $this->createForm(NewPasswordFormType::class, $this->getUser());
        $form->handleRequest($request);

        $user = $userRepository->findOneBy(['username'=>$username]);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('password')->getData() !== $form->get('confirmPassword')->getData()) {
                $this->addFlash('error', 'Passwords do not match');
                return $this->redirectToRoute('app_staff', ['username' => $user->getUsername()]);
            }

            $this->getUser()->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $userRepository->createQueryBuilder('u')
                ->update()
                ->set('u.password', ':password')
                ->where('u.id = :id')
                ->setParameter('password', $user->getPassword())
                ->setParameter('id', $user->getId())
                ->getQuery()
                ->execute();

            return $this->redirectToRoute('app_logout');
        }

        return $this->render('staff/index.html.twig', [
            'user' => $user,
            'categories' => $categoryRepository->findAll(),
            'form' => $form->createView()
        ]);
    }
}
