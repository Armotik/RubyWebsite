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
    #[IsGranted('AUTH_READ', message: 'Only an admin, webmaster or bot can show all staffs')]
    public function index(
        UserRepository      $userRepository,
        SerializerInterface $serializer
    ): Response
    {

        $users = $userRepository->findAll();

        $circularReferenceHandler = function ($object) {
            return $object->getId();
        };

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => $circularReferenceHandler,
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['password', 'value'],
        ];

        $jsonUsers = $serializer->serialize($users, 'json', $context);

        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }


    /**
     * Show a staff (user)
     * @param User $staff The staff to show
     * @param SerializerInterface $serializer The serializer
     * @return Response The response
     */
    #[Route('/api/staffs/{username}', name: 'app_api_staffs_show', methods: ['GET'])]
    #[IsGranted('AUTH_READ', message: 'Only an admin can show a staff')]
    public function show(
        User                $staff,
        SerializerInterface $serializer
    ): Response
    {

        $circularReferenceHandler = function ($object) {
            return $object->getId();
        };

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => $circularReferenceHandler,
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['password'],
        ];

        $jsonStaff = $serializer->serialize($staff, 'json', $context);

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
    #[IsGranted('AUTH_CREATE', message: 'Only an admin can create a staff')]
    public function create(
        Request                     $request,
        SerializerInterface         $serializer,
        EntityManagerInterface      $em,
        UrlGeneratorInterface       $urlGenerator,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface          $validator
    ): Response
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
     * @param Request $request The request
     * @param SerializerInterface $serializer The serializer
     * @param User $currentStaff The current staff
     * @param EntityManagerInterface $em The entity manager
     * @return Response The response
     */
    #[Route('/api/staffs/{username}', name: 'app_api_staffs_update', methods: ['PUT'])]
    #[IsGranted('AUTH_UPDATE', message: 'Only an admin can update a staff')]
    public function update(
        Request                $request,
        SerializerInterface    $serializer,
        User                   $currentStaff,
        EntityManagerInterface $em,
    ): Response
    {
        $updatedStaff = $serializer->deserialize($request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentStaff]
        );

        $content = $request->getContent();
        $password = $serializer->decode($content, 'json')['password'] ?? null;
        $token = $serializer->decode($content, 'json')['apiToken'] ?? null;

        // Not be able to update the password
        if ($password !== null) {
            return new JsonResponse("You can't update password !", Response::HTTP_BAD_REQUEST);
        }

        // Not be able to update the apiToken
        if ($token !== null) {
            return new JsonResponse("You can't update apiToken !", Response::HTTP_BAD_REQUEST);
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
    #[IsGranted('AUTH_DELETE', message: 'Only an admin can delete a staff')]
    public function delete(
        User                   $staff,
        EntityManagerInterface $em
    ): Response
    {
        $em->remove($staff);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

