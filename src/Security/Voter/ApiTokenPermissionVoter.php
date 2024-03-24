<?php

namespace App\Security\Voter;

use App\Repository\TokenRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiTokenPermissionVoter extends Voter
{

    const AUTH_ALL = "AUTH_ALL";
    const AUTH_CREATE = 'AUTH_CREATE';
    const AUTH_READ = 'AUTH_READ';
    const AUTH_UPDATE = 'AUTH_UPDATE';
    const AUTH_DELETE = 'AUTH_DELETE';
    const AUTH_IMAGE_GET = 'AUTH_IMAGE_GET';
    const AUTH_IMAGE_POST = 'AUTH_IMAGE_POST';

    public function __construct(private readonly TokenRepository $tokenRepository)
    {
    }

    /**
     * Check if the voter supports the attribute
     * @param string $attribute The attribute
     * @param mixed $subject The subject
     * @return bool The result of the check
     */
    protected function supports(string $attribute, mixed $subject): bool
    {

        return in_array($attribute, [
            self::AUTH_ALL,
            self::AUTH_CREATE,
            self::AUTH_READ,
            self::AUTH_UPDATE,
            self::AUTH_DELETE,
            self::AUTH_IMAGE_GET,
            self::AUTH_IMAGE_POST
        ]);
    }

    /**
     * Vote on the attribute
     * @param string $attribute The attribute
     * @param mixed $subject The subject
     * @param TokenInterface $token The user token
     * @return bool The result of the vote
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        $user = $token->getUser();

        if ($user->getId() === null) {
            return false;
        }

        if (in_array("ROLE_WEBMASTER", $user->getRoles())) {
            return true;
        }

        $tokens = $this->tokenRepository->findByUser($user);

        foreach ($tokens as $client_token) {

            foreach ($client_token->getAuthorizations() as $auth) {

                if ($auth === self::AUTH_ALL) {
                    return true;
                }

                if ($auth === $attribute) {

                    return true;
                }
            }
        }

        return false;
    }
}