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

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\LinePositions;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteInvoiceBelongsToSameCompany;
use SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteWithinInvoiceTotal;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A credit note reduces the balance of the invoice it references, or, when it has no invoice
 * reference, grants client credit directly. See the `design` document on SOL-69 for the full
 * modelling rationale.
 */
#[ORM\Table(name: CreditNote::TABLE_NAME)]
#[ORM\Index(columns: ['company_id', 'status'])]
#[ORM\UniqueConstraint(columns: ['company_id', 'credit_note_id'])]
#[ORM\Entity(repositoryClass: CreditNoteRepository::class)]
#[CreditNoteInvoiceBelongsToSameCompany]
#[CreditNoteWithinInvoiceTotal]
class CreditNote extends BaseInvoice implements Stringable
{
    final public const string TABLE_NAME = 'credit_notes';

    use Archivable;
    use LinePositions;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'credit_note_id', type: Types::STRING, length: 255)]
    private string $creditNoteId = '';

    #[ORM\Column(name: 'uuid', type: Types::STRING, length: 36)]
    private string $uuid;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25, enumType: CreditNoteStatus::class)]
    private CreditNoteStatus $status = CreditNoteStatus::Draft;

    #[ORM\Column(name: 'invoice_type_code', type: Types::STRING, length: 4, enumType: InvoiceTypeCode::class, options: ['default' => InvoiceTypeCode::CreditNote->value])]
    private InvoiceTypeCode $invoiceTypeCode = InvoiceTypeCode::CreditNote;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank]
    private ?Client $client = null;

    /**
     * The invoice this credit note corrects. Null means a standalone credit note, which grants
     * client credit directly rather than reducing an invoice balance — see `design` §0 on SOL-69.
     */
    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Invoice $invoice = null;

    #[ORM\Column(name: 'credit_note_date', type: Types::DATE_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $creditNoteDate;

    #[ORM\Column(name: 'reason', type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    /**
     * @var Collection<int, CreditNoteLine>
     */
    #[ORM\OneToMany(targetEntity: CreditNoteLine::class, mappedBy: 'creditNote', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lines;

    /**
     * @var Collection<int, InvoiceTax>
     */
    #[ORM\OneToMany(targetEntity: InvoiceTax::class, mappedBy: 'creditNote', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $invoiceTaxes;

    public function __construct()
    {
        parent::__construct();

        $this->lines = new ArrayCollection();
        $this->invoiceTaxes = new ArrayCollection();
        $this->creditNoteDate = CarbonImmutable::now();
        $this->setUuid(Uuid::v7());
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCreditNoteId(): string
    {
        return $this->creditNoteId;
    }

    public function setCreditNoteId(string $creditNoteId): self
    {
        $this->creditNoteId = $creditNoteId;

        return $this;
    }

    public function getUuid(): Uuid
    {
        return Uuid::fromString($this->uuid);
    }

    public function setUuid(Uuid $uuid): self
    {
        $this->uuid = $uuid->toString();

        return $this;
    }

    public function getStatus(): CreditNoteStatus
    {
        return $this->status;
    }

    public function setStatus(CreditNoteStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusValue(): string
    {
        return $this->status->value;
    }

    public function setStatusValue(string $status): self
    {
        $this->status = CreditNoteStatus::from($status);

        return $this;
    }

    public function getInvoiceTypeCode(): InvoiceTypeCode
    {
        return $this->invoiceTypeCode;
    }

    public function setInvoiceTypeCode(InvoiceTypeCode $invoiceTypeCode): self
    {
        $this->invoiceTypeCode = $invoiceTypeCode;

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

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getCreditNoteDate(): DateTimeImmutable
    {
        return $this->creditNoteDate;
    }

    public function setCreditNoteDate(DateTimeInterface $creditNoteDate): self
    {
        // The column is immutable; DBAL 4 rejects mutable values, so normalise here
        // to accept any DateTimeInterface from callers, mirroring TimeStampable.
        $this->creditNoteDate = $creditNoteDate instanceof DateTimeImmutable ? $creditNoteDate : DateTimeImmutable::createFromInterface($creditNoteDate);

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function addLine(CreditNoteLine $line): self
    {
        $this->lines->add($line);
        $line->setCreditNote($this);

        $this->compactLinePositions($this->lines);

        return $this;
    }

    public function removeLine(CreditNoteLine $line): self
    {
        $this->lines->removeElement($line);
        $line->setCreditNote(null);

        $this->compactLinePositions($this->lines);

        return $this;
    }

    /**
     * @return Collection<int, CreditNoteLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    /**
     * The owner's last word before its lines are written: a line that reached the collection
     * without {@see self::addLine()} is still unplaced, and the line's own fallback cannot see
     * its siblings. Renumbering here gives every line a distinct slot.
     */
    #[ORM\PrePersist]
    public function updateLines(): void
    {
        foreach ($this->lines as $line) {
            $line->setCreditNote($this);
        }

        $this->compactLinePositions($this->lines);
    }

    /**
     * @return Collection<int, InvoiceTax>
     */
    public function getInvoiceTaxes(): Collection
    {
        return $this->invoiceTaxes;
    }

    public function addInvoiceTax(InvoiceTax $invoiceTax): self
    {
        if (! $this->invoiceTaxes->contains($invoiceTax)) {
            $this->invoiceTaxes->add($invoiceTax);
            $invoiceTax->setCreditNote($this);
        }

        return $this;
    }

    public function removeInvoiceTax(InvoiceTax $invoiceTax): self
    {
        if ($this->invoiceTaxes->removeElement($invoiceTax) && $invoiceTax->getCreditNote() === $this) {
            $invoiceTax->setCreditNote(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->creditNoteId;
    }
}
