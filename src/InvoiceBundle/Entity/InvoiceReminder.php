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

namespace SolidInvoice\InvoiceBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: InvoiceReminder::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['company_id', 'invoice_id', 'reminder_type'])]
#[ORM\Entity(repositoryClass: InvoiceReminderRepository::class)]
class InvoiceReminder
{
    final public const TABLE_NAME = 'invoice_reminders';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    #[ORM\Column(name: 'reminder_type', length: 20, enumType: ReminderType::class)]
    private ReminderType $reminderType;

    #[ORM\Column(name: 'status', length: 20, nullable: false, enumType: ReminderStatus::class)]
    private ReminderStatus $status = ReminderStatus::Sent;

    #[ORM\Column(name: 'sent_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $sentAt = null;

    #[ORM\Column(name: 'failure_reason', type: Types::TEXT, nullable: true)]
    private ?string $failureReason = null;

    public function getId(): ?Ulid
    {
        return $this->id;
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

    public function getReminderType(): ReminderType
    {
        return $this->reminderType;
    }

    public function setReminderType(ReminderType $reminderType): self
    {
        $this->reminderType = $reminderType;

        return $this;
    }

    public function getStatus(): ReminderStatus
    {
        return $this->status;
    }

    public function setStatus(ReminderStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;

        return $this;
    }
}
