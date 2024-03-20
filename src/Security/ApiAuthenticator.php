<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{

    /**
     * Check if the request is supported
     * @param Request $request The request
     * @return bool|null True if the request is supported, false if it is not, null if it is not clear
     */
    public function supports(Request $request): ?bool
    {

        return $request->headers->has('Authorization') && str_contains($request->headers->get('Authorization') , 'Bearer');
    }

    /**
     * Authenticate the request
     * @param Request $request The request
     * @return Passport The passport
     */
    public function authenticate(Request $request): Passport
    {

        $token = str_replace('Bearer ', '', $request->headers->get('Authorization'));
        return new SelfValidatingPassport(
            new UserBadge($token)
        );
    }

    /**
     * Handle the success authentication
     * @param Request $request The request
     * @param TokenInterface $token The token
     * @param string $firewallName The firewall name
     * @return Response|null The response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * Handle the failure authentication
     * @param Request $request The request
     * @param AuthenticationException $exception The exception
     * @return Response|null The response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {

        return new JsonResponse([
            'message' => 'Authentication failed'
        ], Response::HTTP_UNAUTHORIZED);
    }
}