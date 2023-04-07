<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="SolidInvoice\UserBundle\Repository\UserRepository")
 * @Gedmo\Loggable
 * @UniqueEntity(fields={"email"}, message="This email is already in use. Do you want to log in instead?")
 * @UniqueEntity(fields={"username"}, message="This username is already in use")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimeStampable;

    /**
     * @var UuidInterface|null
     *
     * @ORM\Column(type="uuid_binary_ordered_time")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mobile", type="string", nullable=true)
     */
    private $mobile;

    /**
     * @var Collection<int, ApiToken>
     *
     * @ORM\OneToMany(targetEntity="ApiToken", mappedBy="user", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $apiTokens;

    /**
     * @var string|null
     *
     * @ORM\Column(name="username", type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string")
     */
    private $password;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="confirmation_token", type="string", length=180, nullable=true, unique=true)
     */
    private $confirmationToken;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    private $passwordRequestedAt;

    /**
     * @var string[]
     *
     * @ORM\Column(name="roles", type="array")
     */
    private $roles = [];

    /**
     * @var Collection<int, Company>
     *
     * @ORM\ManyToMany(targetEntity=Company::class, inversedBy="users")
     */
    private Collection $companies;

    public function __construct()
    {
        $this->apiTokens = new ArrayCollection();
        $this->companies = new ArrayCollection();
    }

    /**
     * Don't return the salt, and rely on password_hash to generate a salt.
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @return Collection<int, ApiToken>
     */
    public function getApiTokens(): Collection
    {
        return $this->apiTokens;
    }

    /**
     * @param Collection<int, ApiToken> $apiTokens
     */
    public function setApiTokens(Collection $apiTokens): self
    {
        $this->apiTokens = $apiTokens;

        return $this;
    }

    /**
     * @return string
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    public function addRole($role)
    {
        $role = strtoupper($role);
        if ('ROLE_USER' === $role) {
            return $this;
        }

        if (! in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function serialize()
    {
        return serialize([
            $this->password,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->roles,
            $this->mobile,
            $this->created,
            $this->updated,
        ]);
    }

    public function unserialize($serialized): void
    {
        [
            $this->password,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->roles,
            $this->created,
            $this->updated
        ] = unserialize($serialized);
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Gets the last login time.
     *
     * @return DateTimeInterface|null
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function setEnabled($boolean)
    {
        $this->enabled = (bool) $boolean;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    public function setLastLogin(DateTime $time = null)
    {
        $this->lastLogin = $time;

        return $this;
    }

    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function setPasswordRequestedAt(DateTime $date = null)
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return DateTimeInterface|null
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof DateTime &&
            $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    public function setRoles(array $roles)
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): self
    {
        if (! $this->companies->contains($company)) {
            $this->companies[] = $company;
        }

        return $this;
    }

    public function removeCompany(Company $company): self
    {
        if ($this->companies->contains($company)) {
            $this->companies->removeElement($company);
        }

        return $this;
    }
}
