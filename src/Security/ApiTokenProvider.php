<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\TokenRevokedException;
use App\Exception\UserNotFoundException;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiTokenProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepository $userRepository, private readonly TokenRepository $tokenRepository)
    {
    }


    public function refreshUser(UserInterface $user)
    {
        // TODO: Implement refreshUser() method.
    }

    /**
     * Check if class is supported
     * @param string $class | class name
     * @return bool | true if class is supported
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Load user by token
     * @param string $identifier | token value
     * @return UserInterface $user | UserNotFoundException
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {

        $user = $this->userRepository->findOneByToken($identifier);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $token = $this->tokenRepository->findOneBy(['value' => $identifier]);

        $revocationDate = $token->getRevocationDate();

        if ($revocationDate && $revocationDate < new DateTime()) {
            throw new TokenRevokedException();
        }

        return $user;
    }
}