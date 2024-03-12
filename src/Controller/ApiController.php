<?php

namespace App\Controller;

use ApiPlatform\Api\UrlGeneratorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiController extends AbstractController
{
    /**
     * Show all staffs (users)
     * @param UserRepository $userRepository The repository of the User entity
     * @param SerializerInterface $serializer The serializer
     * @return Response The response
     */
    #[Route('/api/staffs', name: 'app_api_staffs', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only an admin, webmaster or bot can show all staffs')]
    public function index(UserRepository $userRepository, SerializerInterface $serializer): Response
    {
        $staffList = $userRepository->findAll();
        $jsonStaffList = $serializer->serialize($staffList, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['password']]);
        return new JsonResponse($jsonStaffList, Response::HTTP_OK, [], true);
    }


    /**
     * Show a staff (user)
     * @param User $staff The staff to show
     * @param SerializerInterface $serializer The serializer
     * @return Response The response
     */
    #[Route('/api/staffs/{username}', name: 'app_api_staffs_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only an admin can show a staff')]
    public function show(User $staff, SerializerInterface $serializer): Response
    {
        // serialize the staff but not the password
        $jsonStaff = $serializer->serialize($staff, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['password']]);

        return new JsonResponse($jsonStaff, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Create a staff (user)
     * Auto encode the password
     * @param Request $request The request
     * @param SerializerInterface $serializer The serializer
     * @param EntityManagerInterface $em The entity manager
     * @param UrlGeneratorInterface $urlGenerator The url generator
     * @param UserPasswordHasherInterface $passwordHasher The password hasher
     * @param ValidatorInterface $validator The validator
     * @return Response The response
     */
    #[Route('/api/staffs', name: 'app_api_staffs_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only an admin can create a staff')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {

        $staff = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($staff);

        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST);
        }

        if (empty($staff->getUsername()) || empty($staff->getPassword())) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $password = $staff->getPassword();
        $staff->setPassword($passwordHasher->hashPassword($staff, $password));

        $em->persist($staff);

        $em->flush();

        $jsonStaff = $serializer->serialize($staff, 'json');
        $location = $urlGenerator->generate('app_api_staffs_show', ['username' => $staff->getUsername()]);
        return new JsonResponse($jsonStaff, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * Update a staff (user)
     * Not be able to update the password
     * @param string $username The username of the staff to update
     * @param Request $request The request
     * @param SerializerInterface $serializer The serializer
     * @param User $currentStaff The current staff
     * @param EntityManagerInterface $em The entity manager
     * @param UserRepository $userRepository The repository of the User entity
     * @return Response The response
     */
    #[Route('/api/staffs/{username}', name: 'app_api_staffs_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only an admin can update a staff')]
    public function update(string $username, Request $request, SerializerInterface $serializer, User $currentStaff, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $updatedStaff = $serializer->deserialize($request->getContent(),
            User::class,
            'json',
        [AbstractNormalizer::OBJECT_TO_POPULATE => $currentStaff]
        );

        $content = $request->getContent();
        $password = $serializer->decode($content, 'json')['password'] ?? null;

        // Not be able to update the password
        if (empty($content) || $password !== null) {
            return new JsonResponse("You can't update password !", Response::HTTP_BAD_REQUEST);
        }

        // update the username if it's different from the current one
        if ($updatedStaff->getUsername() !== $currentStaff->getUsername()) {
            $currentStaff->setUsername($updatedStaff->getUsername());
        }

        // update the roles if it's different from the current one
        if ($updatedStaff->getRoles() !== $currentStaff->getRoles()) {
            $currentStaff->setRoles($updatedStaff->getRoles());
        }

        $em->persist($currentStaff);

        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete a staff (user)
     * @param User $staff The staff to delete
     * @param EntityManagerInterface $em The entity manager
     * @return Response The response
     */
    #[Route('/api/staffs/{username}', name: 'app_api_staffs_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only an admin can delete a staff')]
    public function delete(User $staff, EntityManagerInterface $em): Response
    {
        $em->remove($staff);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

