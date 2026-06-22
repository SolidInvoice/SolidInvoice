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

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\UserBundle\Enum\InvitationStatus;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: UserInvitation::TABLE_NAME)]
#[ORM\Entity(repositoryClass: UserInvitationRepository::class)]
#[UniqueEntity(fields: ['email', 'company'], message: 'users.invitation.exists')]
class UserInvitation
{
    final public const string TABLE_NAME = 'user_invitations';

    use CompanyAware;

    /**
     * Number of days an invitation remains valid after it is created.
     */
    final public const int VALIDITY_DAYS = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[ORM\Column(type: UlidType::NAME)]
    private ?Ulid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT)]
    private string $email = '';

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private readonly DateTimeInterface $created;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $reminderSentAt = null;

    #[ORM\Column(type: Types::STRING, enumType: InvitationStatus::class)]
    private InvitationStatus $status = InvitationStatus::Pending;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'invited_by_id', nullable: false)]
    private ?User $invitedBy = null;

    public function __construct()
    {
        $now = CarbonImmutable::now();
        $this->created = $now;
        $this->expiresAt = $now->addDays(self::VALIDITY_DAYS);
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getInvitedBy(): ?User
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(?User $invitedBy): self
    {
        $this->invitedBy = $invitedBy;

        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Extends the validity window, starting from now, and returns the invitation
     * to a pending state. Used when an invitation is re-sent so that the new link
     * is usable for the full validity period again.
     */
    public function renew(): self
    {
        $this->expiresAt = CarbonImmutable::now()->addDays(self::VALIDITY_DAYS);
        $this->status = InvitationStatus::Pending;
        $this->reminderSentAt = null;

        return $this;
    }

    public function markExpired(): self
    {
        $this->status = InvitationStatus::Expired;

        return $this;
    }

    public function getReminderSentAt(): ?DateTimeImmutable
    {
        return $this->reminderSentAt;
    }

    public function markReminderSent(): self
    {
        $this->reminderSentAt = CarbonImmutable::now();

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->status === InvitationStatus::Expired
            || ($this->expiresAt instanceof DateTimeInterface
                && $this->expiresAt < CarbonImmutable::now());
    }

    public function getStatus(): InvitationStatus
    {
        return $this->status;
    }

    public function setStatus(InvitationStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
