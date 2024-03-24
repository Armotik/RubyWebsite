<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class TokenController extends AbstractController
{

    /**
     * Generate an api token for a staff (user)
     * @param int $length The length of the api token
     * @param EntityManagerInterface $em The entity manager
     * @return Response The response
     * @throws RandomException If an error occurs while generating random bytes
     */
    #[Route('/api/token/{username}/create', name: 'app_api_token_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_MANAGER', message: 'Only an admin manager can generate an api token')]
    public function generateApiToken(
        User                   $staff,
        EntityManagerInterface $em,
        Request                $request,
        SerializerInterface    $serializer,
        int                    $length = 64,
    ): Response
    {

        if ($staff->getId() === null) {
            return new JsonResponse("The staff doesn't exist !", Response::HTTP_NOT_FOUND);
        }

        $tokenName = $serializer->decode($request->getContent(), 'json')['name'] ?? null;
        $revocationDate = $serializer->decode($request->getContent(), 'json')['revocationDate'] ?? null;
        $authorisations = $serializer->decode($request->getContent(), 'json')['authorizations'] ?? null;

        if ($tokenName === null) {
            return new JsonResponse("You must provide a name for the token !", Response::HTTP_BAD_REQUEST);
        }

        try {
            $randomBytes = random_bytes($length / 2);

            if ($revocationDate !== null) {
                $revocationDate = new DateTime($revocationDate);
            }
        } catch (Exception $e) {
            return new JsonResponse("An error occurred while creating the token !", Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        $apiToken = bin2hex($randomBytes);

        $token = new Token();
        $token->setName($tokenName);
        $token->setUser($staff);
        $token->setValue($apiToken);
        $token->setRevocationDate($revocationDate);
        $token->setAuthorizations($authorisations);

        $em->persist($token);
        $em->flush();

        $circularReferenceHandler = function ($object) {
            return $object->getId();
        };

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => $circularReferenceHandler,
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['password'],
            AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'revocationDate', 'authorizations', 'value', 'user' => ['id', 'username']],
        ];

        $jsonToken = $serializer->serialize($token, 'json', $context);

        return new JsonResponse($jsonToken, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Delete a token
     * @param Token $token The token to delete
     * @param EntityManagerInterface $em The entity manager
     * @return Response The response
     */
    #[Route('/api/token/{token}', name: 'app_api_token_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN_MANAGER', message: 'Only an admin manager can delete a token')]
    public function delete(
        Token                  $token,
        EntityManagerInterface $em
    ): Response
    {
        $em->remove($token);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Update a token
     * @param Token $token The token to update
     * @param EntityManagerInterface $em The entity manager
     * @return Response The response
     */
    #[Route('/api/token/{token}', name: 'app_api_token_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN_MANAGER', message: 'Only an admin manager can update a token')]
    public function update(
        Token                  $token,
        EntityManagerInterface $em,
        Request                $request,
        SerializerInterface    $serializer
    ): Response
    {
        $content = $request->getContent();
        $name = $serializer->decode($content, 'json')['name'] ?? null;
        $revocationDate = $serializer->decode($content, 'json')['revocationDate'] ?? null;
        $authorisations = $serializer->decode($content, 'json')['authorisations'] ?? null;

        if ($name !== null) {
            $token->setName($name);
        }

        if ($revocationDate !== null) {
            $token->setRevocationDate($revocationDate);
        }

        if ($authorisations !== null) {
            $token->setAuthorizations($authorisations);
        }

        $em->persist($token);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Get all tokens
     * @param TokenRepository $tokenRepository The token repository
     * @param SerializerInterface $serializer The serializer
     * @return Response The response
     */
    #[Route('/api/tokens', name: 'app_api_tokens_get', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN_MANAGER', message: 'Only an admin manager can get all tokens')]
    public function getAll(
        TokenRepository     $tokenRepository,
        SerializerInterface $serializer
    ): Response
    {

        $tokenList = $tokenRepository->findAll();

        $circularReferenceHandler = function ($object) {
            return $object->getId();
        };

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => $circularReferenceHandler,
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['value', 'password'],
        ];

        $jsonTokenList = $serializer->serialize($tokenList, 'json', $context);
        return new JsonResponse($jsonTokenList, Response::HTTP_OK, [], true);
    }
}