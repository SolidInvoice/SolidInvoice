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

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\EInvoiceBundle\Action\Api\DownloadPayload;
use SolidInvoice\EInvoiceBundle\Action\Api\DownloadReceipt;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Exception\DocumentAlreadyClearedException;
use SolidInvoice\EInvoiceBundle\Repository\EInvoiceDocumentRepository;
use SolidInvoice\EInvoiceBundle\Validator\Constraints\ExactlyOneSourceDocument;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
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
#[ApiFilter(SearchFilter::class, properties: [
    'status' => 'exact', 'channel' => 'exact', 'profile' => 'exact',
    'externalId' => 'exact', 'invoice' => 'exact', 'creditNote' => 'exact',
    'sourceNumber' => 'partial',
])]
#[ApiFilter(DateFilter::class, properties: ['created', 'transmittedAt', 'completedAt'])]
#[ApiFilter(OrderFilter::class, properties: ['created', 'transmittedAt', 'completedAt', 'status'])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new GetCollection(
            uriTemplate: '/invoices/{invoiceId}/e-invoice-documents',
            uriVariables: [
                'invoiceId' => new Link(toProperty: 'invoice', fromClass: Invoice::class),
            ],
        ),
        new Get(
            uriTemplate: '/e-invoice-documents/{id}/payload',
            controller: DownloadPayload::class,
            openapi: new OpenApiOperation(responses: [
                200 => new OpenApiResponse(description: 'The stored e-invoice payload, verbatim.'),
                404 => new OpenApiResponse(description: 'Document not found.'),
            ]),
            output: false,
            name: 'e_invoice_document_payload',
        ),
        new Get(
            uriTemplate: '/e-invoice-documents/{id}/receipt',
            controller: DownloadReceipt::class,
            openapi: new OpenApiOperation(responses: [
                200 => new OpenApiResponse(description: 'The platform clearance receipt, verbatim.'),
                404 => new OpenApiResponse(description: 'Document not found, or not yet cleared.'),
            ]),
            output: false,
            name: 'e_invoice_document_receipt',
        ),
    ],
    normalizationContext: [
        'groups' => ['e_invoice_document_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    graphQlOperations: [
        new Query(normalizationContext: ['groups' => ['e_invoice_document_api:read']]),
        new QueryCollection(normalizationContext: ['groups' => ['e_invoice_document_api:read']]),
    ],
)]
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
    #[Groups(['e_invoice_document_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25, enumType: EInvoiceStatus::class)]
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    private EInvoiceStatus $status = EInvoiceStatus::Pending;

    /**
     * Lowercase SHA-256 hex of {@see self::$payload}, computed in the constructor. Never accepted
     * as a constructor argument: a hash passed in by a caller is a hash that can disagree with
     * the payload it describes.
     */
    #[ORM\Column(name: 'payload_hash', type: Types::STRING, length: 64)]
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    private string $payloadHash;

    /**
     * The identifier assigned by the remote platform.
     */
    #[ORM\Column(name: 'external_id', type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    private ?string $externalId = null;

    /**
     * The platform's receipt document (e.g. a KSeF UPO), verbatim. Deliberately not readable
     * through the API: `#[Groups]` alone does not keep an ungrouped property out of the
     * normalized output here, so this needs the explicit `readable: false` — see `hasReceipt()`
     * for the boolean clients get instead (`design` §5.2 on SOL-94).
     */
    #[ORM\Column(name: 'receipt', type: Types::TEXT, nullable: true)]
    #[ApiProperty(readable: false, writable: false)]
    private ?string $receipt = null;

    #[ORM\Column(name: 'error_code', type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    private ?string $errorCode = null;

    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true)]
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'transmitted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    private ?DateTimeImmutable $transmittedAt = null;

    /**
     * Set on entering a terminal place.
     */
    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    private ?DateTimeImmutable $completedAt = null;

    /**
     * Written by the polling scan (#2679). The column lands here because adding it later would
     * cost a second migration on a table this issue is creating — `design` §8.3. Not readable
     * through the API: exposing it would invite a client to treat it as a promise about when a
     * status will change (`design` §5.2).
     */
    #[ORM\Column(name: 'poll_after', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[ApiProperty(readable: false, writable: false)]
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
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
        private ?Invoice $invoice,
        #[ORM\ManyToOne(targetEntity: CreditNote::class)]
        #[ORM\JoinColumn(name: 'credit_note_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
        private ?CreditNote $creditNote,
        /**
         * Denormalised from the source document's own number, so the audit record survives a hard
         * delete of its invoice or credit note (both FKs are `SET NULL`) — `design` §3.3.
         */
        #[ORM\Column(name: 'source_number', type: Types::STRING, length: 255)]
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
        private string $sourceNumber,
        #[ORM\Column(name: 'channel', type: Types::STRING, length: 50)]
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
        private string $channel,
        /**
         * A {@see \SolidInvoice\EInvoiceBundle\Profile\ProfileInterface::getIdentifier()} value, e.g.
         * `xrechnung-3.0.2`.
         */
        #[ORM\Column(name: 'profile', type: Types::STRING, length: 100)]
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
        private string $profile,
        /**
         * The syntax payload (UBL or CII XML), verbatim. This is never a Factur-X PDF: Factur-X
         * packaging (#2670) wraps this XML into a PDF/A-3 at send time and does not need a second
         * payload column — `design` §3.1. Not readable through the API: a multi-megabyte XML body
         * has no business inside a collection response — fetch it from `/payload` instead
         * (`design` §5.2).
         */
        #[ORM\Column(name: 'payload', type: Types::TEXT)]
        #[ApiProperty(readable: false, writable: false)]
        private string $payload,
        #[ORM\Column(name: 'payload_format', type: Types::STRING, length: 10, enumType: SyntaxFormat::class)]
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
        private SyntaxFormat $payloadFormat,
        #[ORM\Column(name: 'payload_media_type', type: Types::STRING, length: 100)]
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
        private string $payloadMediaType,
        #[ORM\Column(name: 'payload_filename', type: Types::STRING, length: 255)]
        #[Groups(['e_invoice_document_api:read'])]
        #[ApiProperty(writable: false)]
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

    /**
     * Tells an API or MCP client whether `/receipt` will 200 or 404, without exposing the receipt
     * body itself — `design` §5.2 on SOL-94.
     *
     * Named `getHasReceipt()`, not `hasReceipt()`: the serializer strips a bare `has`/`is`/`get`
     * prefix to find the logical property a method belongs to, so `hasReceipt()` would collapse
     * onto the `$receipt` property itself and inherit its `readable: false` — a `get` prefix keeps
     * `HasReceipt` intact as its own property, and `#[SerializedName]` puts the key back to the
     * `hasReceipt` the design calls for.
     */
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    #[SerializedName('hasReceipt')]
    public function getHasReceipt(): bool
    {
        return $this->receipt !== null;
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
     * Exposes {@see TimeStampable::$created} under the API's `created` key. The trait's own
     * property carries a blanket `#[Ignore]` that `#[Groups]` cannot lift (Symfony's `Ignore`
     * attribute takes no group parameter), and the serializer resolves a method's logical
     * property by stripping its `get`/`is`/`has` prefix — so a same-named override (`getCreated`)
     * would still collapse onto the ignored `$created` property. `getCreatedAt()` is its own,
     * un-ignored logical property; `#[SerializedName]` puts the key back to `created`.
     */
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    #[SerializedName('created')]
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @see self::getCreatedAt() for why this exists instead of overriding
     *      {@see TimeStampable::getUpdated()} directly
     */
    #[Groups(['e_invoice_document_api:read'])]
    #[ApiProperty(writable: false)]
    #[SerializedName('updated')]
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated;
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
