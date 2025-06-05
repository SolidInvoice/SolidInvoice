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

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EmailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\NilUlid;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: User::TABLE_NAME)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'This email is already in use. Do you want to log in instead?')]
#[ORM\Index(fields: ['googleId'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, Stringable, TotpTwoFactorInterface, EmailTwoFactorInterface, TrustedDeviceInterface, BackupCodeInterface
{
    final public const TABLE_NAME = 'users';

    use TimeStampable;

    #[ORM\Column(type: UlidType::NAME, unique: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING, length: 45, nullable: true)]
    #[Assert\NotBlank()]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 45, nullable: true)]
    #[Assert\NotBlank()]
    private ?string $lastName = null;

    #[ORM\Column(name: 'mobile', type: Types::STRING, nullable: true)]
    private ?string $mobile = null;

    /**
     * @var Collection<int, ApiToken>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiToken::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $apiTokens;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank()]
    #[Assert\Email(
        message: 'The email "{{ value }}" is not a valid email address.',
        mode: Assert\Email::VALIDATION_MODE_STRICT,
    )]
    private ?string $email = null;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN)]
    private bool $enabled = false;

    #[ORM\Column(name: 'verified', type: Types::BOOLEAN)]
    private bool $verified = false;

    #[ORM\Column(name: 'password', type: Types::STRING)]
    private ?string $password = null;

    private string $plainPassword = '';

    #[ORM\Column(name: 'last_login', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastLogin = null;

    /**
     * @var string[]
     */
    #[ORM\Column(name: 'roles', type: 'array')]
    private array $roles = [];

    /**
     * @var Collection<int, Company>
     */
    #[ORM\ManyToMany(targetEntity: Company::class, inversedBy: 'users', cascade: ['persist'])]
    private Collection $companies;

    #[ORM\Column(name: 'google_id', type: Types::STRING, length: 45, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(name: 'totp_secret', type: Types::STRING, length: 45, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(name: 'auth_code', type: Types::STRING, length: 45, nullable: true)]
    private ?string $authCode;

    #[ORM\Column(name: 'email_auth_enabled', type: Types::BOOLEAN, nullable: true)]
    private ?bool $emailAuthEnabled = false;

    #[ORM\Column(name: 'trusted_version', type: Types::INTEGER, options: ['default' => 0])]
    private int $trustedVersion;

    #[ORM\Column(name: 'backup_codes', type: 'json', nullable: true)]
    private ?array $backupCodes = [];

    public function __construct()
    {
        $this->apiTokens = new ArrayCollection();
        $this->companies = new ArrayCollection();
        $this->id = new NilUlid();
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

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function addRole(string $role): self
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

    public function eraseCredentials(): void
    {
        $this->plainPassword = '';
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function getLastLogin(): ?DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function hasRole(string $role): bool
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function removeRole(string $role): self
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setPlainPassword(?string $password): self
    {
        $this->plainPassword = (string) $password;

        return $this;
    }

    public function setLastLogin(?DateTimeInterface $time = null): self
    {
        $this->lastLogin = $time;

        return $this;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): self
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

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpSecret ? true : false;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface | null
    {
        $period = 20;
        $digits = 6;

        return null !== $this->totpSecret ? new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, $period, $digits) : null;
    }

    public function __toString(): string
    {
        return $this->email;
    }

    public function isEmailAuthEnabled(): bool
    {
        return $this->emailAuthEnabled === true;
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): string | null
    {
        if (null === $this->authCode) {
            throw new \LogicException('The email authentication code was not set');
        }

        return $this->authCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }

    public function is2FaEnabled(): bool
    {
        return $this->isTotpAuthenticationEnabled() || $this->isEmailAuthEnabled();
    }

    public function getTrustedTokenVersion(): int
    {
        return $this->trustedVersion;
    }

    public function isBackupCode(string $code): bool
    {
        return in_array($code, (array) $this->backupCodes, true);
    }

    public function invalidateBackupCode(string $code): void
    {
        $key = array_search($code, (array) $this->backupCodes, true);
        if ($key !== false) {
            unset($this->backupCodes[$key]);
        }
    }

    /**
     * @param list<string> $backUpCodes
     */
    public function setBackUpCodes(array $backUpCodes): self
    {
        $this->backupCodes = $backUpCodes;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getBackUpCodes(): array
    {
        return $this->backupCodes ?? [];
    }

    public function enableEmailAuth(bool $enabled): self
    {
        $this->emailAuthEnabled = $enabled;

        return $this;
    }
}
