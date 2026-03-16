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

namespace SolidInvoice\TimeTrackingBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\TimeTrackingBundle\Enum\TimeEntryStatus;
use SolidInvoice\TimeTrackingBundle\Repository\TimeEntryRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'time_entries')]
#[ORM\Entity(repositoryClass: TimeEntryRepository::class)]
class TimeEntry
{
    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Invoice $invoice = null;

    #[ORM\ManyToOne(targetEntity: Timer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Timer $timer = null;

    #[ORM\Column(name: 'entry_date', type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private DateTimeImmutable $date;

    /**
     * Duration in seconds.
     */
    #[ORM\Column(name: 'duration', type: Types::INTEGER)]
    #[Assert\GreaterThan(0)]
    private int $duration = 0;

    /**
     * Hourly rate in cents (smallest currency unit).
     */
    #[ORM\Column(name: 'hourly_rate', type: BigIntegerType::NAME)]
    private BigNumber $hourlyRate;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25, enumType: TimeEntryStatus::class)]
    private TimeEntryStatus $status = TimeEntryStatus::Pending;

    public function __construct()
    {
        $this->date = new DateTimeImmutable();
        $this->hourlyRate = BigDecimal::zero();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getTimer(): ?Timer
    {
        return $this->timer;
    }

    public function setTimer(?Timer $timer): self
    {
        $this->timer = $timer;

        return $this;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getHourlyRate(): BigNumber
    {
        return $this->hourlyRate;
    }

    public function setHourlyRate(BigNumber $hourlyRate): self
    {
        $this->hourlyRate = $hourlyRate;

        return $this;
    }

    public function getStatus(): TimeEntryStatus
    {
        return $this->status;
    }

    public function setStatus(TimeEntryStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->status === TimeEntryStatus::Invoiced;
    }
}
