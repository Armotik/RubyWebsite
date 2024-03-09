<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "The username cannot be blank")]
    #[Assert\Length(
        min: 3,
        max: 20,
        minMessage: "The username must be at least 3 characters long",
        maxMessage: "The username cannot be longer than 20 characters"
    )]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Assert\Choice([
        "choices" => ["ROLE_USER", "ROLE_ADMIN", "ROLES_MOD_TEST", "ROLE_MOD", "ROLE_MOD_PLUS", "ROLE_SUPER_MOD"],
        "message" => "The role must be either ROLE_USER, ROLE_ADMIN, ROLES_MOD_TEST, ROLE_MOD, ROLE_MOD_PLUS, or ROLE_SUPER_MOD"
    ])]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(message: "The password cannot be blank")]
    #[Assert\Length(
        min: 6,
        max: 255,
        minMessage: "The password must be at least 6 characters long",
        maxMessage: "The password cannot be longer than 255 characters"
    )]
    #[Assert\Regex(
        pattern: "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/",
        message: "The password must contain at least one uppercase letter, one lowercase letter, and one number"
    )]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
