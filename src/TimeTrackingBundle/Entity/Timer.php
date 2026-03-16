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

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\TimeTrackingBundle\Enum\TimerStatus;
use SolidInvoice\TimeTrackingBundle\Repository\TimerRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: 'time_tracking_timers')]
#[ORM\Entity(repositoryClass: TimerRepository::class)]
class Timer
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
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25, enumType: TimerStatus::class)]
    private TimerStatus $status = TimerStatus::Running;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $startedAt;

    /**
     * The start of the current running interval. Reset on each resume.
     */
    #[ORM\Column(name: 'last_started_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $lastStartedAt;

    /**
     * Accumulated seconds from all completed running intervals (not counting current).
     */
    #[ORM\Column(name: 'elapsed_seconds', type: Types::INTEGER)]
    private int $elapsedSeconds = 0;

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->startedAt = $now;
        $this->lastStartedAt = $now;
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

    public function setClient(?Client $client): self
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

    public function getStatus(): TimerStatus
    {
        return $this->status;
    }

    public function setStatus(TimerStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getLastStartedAt(): DateTimeImmutable
    {
        return $this->lastStartedAt;
    }

    public function setLastStartedAt(DateTimeImmutable $lastStartedAt): self
    {
        $this->lastStartedAt = $lastStartedAt;

        return $this;
    }

    public function getElapsedSeconds(): int
    {
        return $this->elapsedSeconds;
    }

    public function setElapsedSeconds(int $elapsedSeconds): self
    {
        $this->elapsedSeconds = $elapsedSeconds;

        return $this;
    }
}
