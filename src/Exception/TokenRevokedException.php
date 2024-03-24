<?php

namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TokenRevokedException extends AuthenticationException
{
    /**
     * Get the message key
     * @return string The message key
     */
    public function getMessageKey(): string
    {
        return 'The token has been revoked.';
    }
}