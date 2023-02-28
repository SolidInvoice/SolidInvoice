<?php
declare(strict_types=1);

namespace SolidInvoice\UserBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=UserInvitationRepository::class)
 * @ORM\Table(name="user_invitations")
 * @UniqueEntity(fields={"email", "company"}, message="users.invitation.exists")
 */
class UserInvitation
{
    use CompanyAware;

    public const STATUS_PENDING = 'pending';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     * @ORM\Column(type="uuid_binary_ordered_time")
     */
    private UuidInterface $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $email = '';

    /**
     * @ORM\Column(type="datetimetz_immutable")
     */
    private DateTimeInterface $created;

    /**
     * @ORM\Column(type="string")
     */
    private string $status;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="invited_by_id", nullable=false)
     */
    private ?User $invitedBy = null;

    public function __construct()
    {
        $this->created = new DateTimeImmutable();
    }

    public function getId(): UuidInterface
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
