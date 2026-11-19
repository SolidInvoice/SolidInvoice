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

namespace SolidInvoice\EInvoiceBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Exception\DocumentAlreadyClearedException;
use SolidInvoice\EInvoiceBundle\Repository\EInvoiceDocumentRepository;
use SolidInvoice\EInvoiceBundle\Validator\Constraints\ExactlyOneSourceDocument;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function hash;

/**
 * An audit record of one attempt to transmit an invoice or credit note as an e-invoice. Tracks
 * its own transmission lifecycle independently of the source document's own status — see
 * `design` §8.1 on SOL-89 for why the two cannot share a marking.
 *
 * Deliberately **not** `Archivable`: an audit record of what was transmitted must not be
 * soft-deletable. See `design` §3 / ADR 0001 §8.2.
 *
 * @see \SolidInvoice\EInvoiceBundle\Tests\Entity\EInvoiceDocumentTest
 */
#[ORM\Table(name: EInvoiceDocument::TABLE_NAME)]
#[ORM\Index(columns: ['status', 'poll_after'])]
#[ORM\Index(columns: ['external_id'])]
#[ORM\Entity(repositoryClass: EInvoiceDocumentRepository::class)]
#[ExactlyOneSourceDocument]
class EInvoiceDocument
{
    final public const string TABLE_NAME = 'einvoice_document';

    use CompanyAware;
    use TimeStampable;

    /**
     * The four fields a terminal document must never let change — checked against the
     * **current** marking, not the transition being applied. See `design` §6 on SOL-89: during
     * the `accept` transition the marking is still `transmitted` when `externalId` and `receipt`
     * are written, so that write must remain allowed.
     */
    public const array IMMUTABLE_FIELDS = ['payload', 'payloadHash', 'externalId', 'receipt'];

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25, enumType: EInvoiceStatus::class)]
    private EInvoiceStatus $status = EInvoiceStatus::Pending;

    /**
     * Lowercase SHA-256 hex of {@see self::$payload}, computed in the constructor. Never accepted
     * as a constructor argument: a hash passed in by a caller is a hash that can disagree with
     * the payload it describes.
     */
    #[ORM\Column(name: 'payload_hash', type: Types::STRING, length: 64)]
    private string $payloadHash;

    /**
     * The identifier assigned by the remote platform.
     */
    #[ORM\Column(name: 'external_id', type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalId = null;

    /**
     * The platform's receipt document (e.g. a KSeF UPO), verbatim.
     */
    #[ORM\Column(name: 'receipt', type: Types::TEXT, nullable: true)]
    private ?string $receipt = null;

    #[ORM\Column(name: 'error_code', type: Types::STRING, length: 100, nullable: true)]
    private ?string $errorCode = null;

    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'transmitted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $transmittedAt = null;

    /**
     * Set on entering a terminal place.
     */
    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    /**
     * Written by the polling scan (#2679). The column lands here because adding it later would
     * cost a second migration on a table this issue is creating — `design` §8.3.
     */
    #[ORM\Column(name: 'poll_after', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $pollAfter = null;

    /**
     * Everything the row needs to exist at `pending` is a constructor argument — `design` §3.2.
     * There is no legitimate flow that calls {@see self::setPayload()} after construction; the
     * guarded setters exist only to make an illegitimate one throw.
     */
    public function __construct(
        Company $company,
        #[ORM\ManyToOne(targetEntity: Invoice::class)]
        #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
        private ?Invoice $invoice,
        #[ORM\ManyToOne(targetEntity: CreditNote::class)]
        #[ORM\JoinColumn(name: 'credit_note_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
        private ?CreditNote $creditNote,
        /**
         * Denormalised from the source document's own number, so the audit record survives a hard
         * delete of its invoice or credit note (both FKs are `SET NULL`) — `design` §3.3.
         */
        #[ORM\Column(name: 'source_number', type: Types::STRING, length: 255)]
        private string $sourceNumber,
        #[ORM\Column(name: 'channel', type: Types::STRING, length: 50)]
        private string $channel,
        /**
         * A {@see \SolidInvoice\EInvoiceBundle\Profile\ProfileInterface::getIdentifier()} value, e.g.
         * `xrechnung-3.0.2`.
         */
        #[ORM\Column(name: 'profile', type: Types::STRING, length: 100)]
        private string $profile,
        /**
         * The syntax payload (UBL or CII XML), verbatim. This is never a Factur-X PDF: Factur-X
         * packaging (#2670) wraps this XML into a PDF/A-3 at send time and does not need a second
         * payload column — `design` §3.1.
         */
        #[ORM\Column(name: 'payload', type: Types::TEXT)]
        private string $payload,
        #[ORM\Column(name: 'payload_format', type: Types::STRING, length: 10, enumType: SyntaxFormat::class)]
        private SyntaxFormat $payloadFormat,
        #[ORM\Column(name: 'payload_media_type', type: Types::STRING, length: 100)]
        private string $payloadMediaType,
        #[ORM\Column(name: 'payload_filename', type: Types::STRING, length: 255)]
        private string $payloadFilename,
    ) {
        $this->company = $company;
        $this->payloadHash = hash('sha256', $this->payload);
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function getCreditNote(): ?CreditNote
    {
        return $this->creditNote;
    }

    public function getSourceNumber(): string
    {
        return $this->sourceNumber;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function getStatus(): EInvoiceStatus
    {
        return $this->status;
    }

    /**
     * Not guarded: this is the plain accessor the `einvoice_document` workflow's marking store
     * uses to move the document between places (SOL-208). The workflow's own guards, not this
     * setter, are what stop an invalid transition.
     */
    public function setStatus(EInvoiceStatus $status): self
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
        $this->status = EInvoiceStatus::from($status);

        return $this;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): self
    {
        $this->assertMutable();

        $this->payload = $payload;

        return $this;
    }

    public function getPayloadHash(): string
    {
        return $this->payloadHash;
    }

    public function setPayloadHash(string $payloadHash): self
    {
        $this->assertMutable();

        $this->payloadHash = $payloadHash;

        return $this;
    }

    public function getPayloadFormat(): SyntaxFormat
    {
        return $this->payloadFormat;
    }

    public function getPayloadMediaType(): string
    {
        return $this->payloadMediaType;
    }

    public function getPayloadFilename(): string
    {
        return $this->payloadFilename;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->assertMutable();

        $this->externalId = $externalId;

        return $this;
    }

    public function getReceipt(): ?string
    {
        return $this->receipt;
    }

    public function setReceipt(?string $receipt): self
    {
        $this->assertMutable();

        $this->receipt = $receipt;

        return $this;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getTransmittedAt(): ?DateTimeImmutable
    {
        return $this->transmittedAt;
    }

    public function setTransmittedAt(?DateTimeImmutable $transmittedAt): self
    {
        $this->transmittedAt = $transmittedAt;

        return $this;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getPollAfter(): ?DateTimeImmutable
    {
        return $this->pollAfter;
    }

    public function setPollAfter(?DateTimeImmutable $pollAfter): self
    {
        $this->pollAfter = $pollAfter;

        return $this;
    }

    /**
     * @throws DocumentAlreadyClearedException when the document's current marking is terminal
     */
    private function assertMutable(): void
    {
        if ($this->status->isTerminal()) {
            throw new DocumentAlreadyClearedException($this->id);
        }
    }
}
