<?php

namespace App\Security\Voter;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApiTokenPermissionVoter extends Voter
{

    const AUTH_ALL = 'AUTH_ALL';
    const AUTH_CREATE = 'AUTH_CREATE';
    const AUTH_READ = 'AUTH_READ';
    const AUTH_UPDATE = 'AUTH_UPDATE';
    const AUTH_DELETE = 'AUTH_DELETE';
    const AUTH_IMAGE_GET = 'AUTH_IMAGE_GET';
    const AUTH_IMAGE_POST = 'AUTH_IMAGE_POST';

    public function __construct(private TokenRepository $tokenRepository)
    {
    }

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

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        // TODO: Token roles not User rolesù
        //dd($token->getRoleNames());

        $user = $token->getUser();

        if ($user->getId() === null) {
            return false;
        }

        $tokens = $this->tokenRepository->findByUser($user);

        foreach ($tokens as $client_token) {

            if ($client_token->getAuthorizations() === [self::AUTH_ALL]) {
                return true;
            }

            if ($client_token->getAuthorizations() === [$attribute]) {
                return true;
            }
        }

        return false;
    }
}