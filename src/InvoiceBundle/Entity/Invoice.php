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

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use ArrayObject;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use SolidInvoice\ApiBundle\DTO\MarkSentInput;
use SolidInvoice\ApiBundle\DTO\SendDocumentInput;
use SolidInvoice\ApiBundle\State\Processor\InvoiceCloneProcessor;
use SolidInvoice\ApiBundle\State\Processor\InvoiceMarkSentProcessor;
use SolidInvoice\ApiBundle\State\Processor\InvoiceReminderProcessor;
use SolidInvoice\ApiBundle\State\Processor\InvoiceTransitionProcessor;
use SolidInvoice\ApiBundle\State\Processor\SendDocumentProcessor;
use SolidInvoice\ApiBundle\State\Provider\InvoiceTransitionProvider;
use SolidInvoice\ApiBundle\State\Provider\OptionalBodyProvider;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Action\Api\BillingPdfAction;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\LinePositions;
use SolidInvoice\CoreBundle\Traits\Entity\SentTo;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Traits\InvoiceStatusTrait;
use SolidInvoice\InvoiceBundle\Validator\Constraints\WithinPlanInvoiceLimit;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Invoice::TABLE_NAME)]
#[ORM\Index(columns: ['quote_id'])]
#[ORM\Index(name: 'idx_invoice_reminder_scan', columns: ['due', 'status'])]
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact', 'client' => 'exact', 'invoiceId' => 'partial', 'client.name' => 'partial'])]
#[ApiFilter(DateFilter::class, properties: ['invoiceDate', 'due', 'paidDate', 'created', 'updated'])]
#[ApiFilter(OrderFilter::class, properties: ['invoiceDate', 'due', 'status', 'created', 'updated'])]
#[ApiFilter(NumericFilter::class, properties: ['balance'])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(extraProperties: ['idempotent' => true]),
        new Patch(),
        new Delete(),
        new GetCollection(
            uriTemplate: '/clients/{clientId}/invoices',
            uriVariables: [
                'clientId' => new Link(
                    fromProperty: 'invoices',
                    fromClass: Client::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/invoices/{id}/transitions/{transition}',
            uriVariables: [
                'id' => new Link(fromClass: Invoice::class),
            ],
            input: false,
            output: Invoice::class,
            name: 'invoice_transition',
            provider: InvoiceTransitionProvider::class,
            processor: InvoiceTransitionProcessor::class,
        ),
        new Get(
            uriTemplate: '/invoices/{id}/pdf',
            outputFormats: ['pdf' => ['application/pdf']],
            controller: BillingPdfAction::class,
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'The invoice PDF',
                        content: new ArrayObject(['application/pdf' => new MediaType(new ArrayObject(['type' => 'string', 'format' => 'binary']))]),
                    ),
                    '304' => new Response(description: 'The invoice has not changed since the ETag was issued'),
                ],
                summary: 'Downloads the invoice as a PDF.',
                description: 'Rendered with the same template as the public invoice page. Send the returned ETag back in If-None-Match to get a 304 while the invoice is unchanged.',
            ),
            serialize: false,
            name: 'invoice_pdf',
        ),
        new Post(
            uriTemplate: '/invoices/{id}/clone',
            uriVariables: [
                'id' => new Link(fromClass: Invoice::class),
            ],
            input: false,
            output: Invoice::class,
            validate: false,
            name: 'invoice_clone',
            provider: InvoiceTransitionProvider::class,
            processor: InvoiceCloneProcessor::class,
        ),
        new Post(
            uriTemplate: '/invoices/{id}/send',
            uriVariables: [
                'id' => new Link(fromClass: Invoice::class),
            ],
            status: 200,
            normalizationContext: [
                'groups' => ['invoice_api:read', 'billing_api:sent'],
                AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
            ],
            denormalizationContext: ['groups' => ['billing_api:send']],
            input: SendDocumentInput::class,
            output: Invoice::class,
            // The body is optional and holds the send options; the processor loads the invoice.
            deserialize: false,
            name: 'invoice_send',
            provider: OptionalBodyProvider::class,
            processor: SendDocumentProcessor::class,
            extraProperties: ['idempotent' => true],
        ),
        new Post(
            uriTemplate: '/invoices/{id}/reminder',
            uriVariables: [
                'id' => new Link(fromClass: Invoice::class),
            ],
            status: 200,
            normalizationContext: [
                'groups' => ['invoice_api:read', 'billing_api:sent'],
                AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
            ],
            input: false,
            output: Invoice::class,
            validate: false,
            name: 'invoice_reminder',
            provider: InvoiceTransitionProvider::class,
            processor: InvoiceReminderProcessor::class,
        ),
        new Post(
            uriTemplate: '/invoices/{id}/mark-sent',
            uriVariables: [
                'id' => new Link(fromClass: Invoice::class),
            ],
            status: 200,
            denormalizationContext: ['groups' => ['billing_api:send']],
            input: MarkSentInput::class,
            output: Invoice::class,
            deserialize: false,
            name: 'invoice_mark_sent',
            provider: OptionalBodyProvider::class,
            processor: InvoiceMarkSentProcessor::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['invoice_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['invoice_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'company', inversedBy: 'invoices')])]
#[WithinPlanInvoiceLimit]
#[UniqueEntity(fields: ['invoiceId'], message: 'invoice.invoice_id.unique', repositoryMethod: 'findByInvoiceIdIncludingArchived')]
class Invoice extends BaseInvoice implements Stringable
{
    final public const string TABLE_NAME = 'invoices';

    use Archivable;
    use InvoiceStatusTrait {
        Archivable::isArchived insteadof InvoiceStatusTrait;
    }
    use LinePositions;
    use SentTo;
    use TimeStampable;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25, enumType: InvoiceStatus::class)]
    #[Groups(['invoice_api:read', 'searchable'])]
    #[ApiProperty(writable: false)]
    protected ?InvoiceStatus $status = null;

    #[ORM\Column(name: 'invoice_type_code', type: Types::STRING, length: 4, enumType: InvoiceTypeCode::class, options: ['default' => InvoiceTypeCode::CommercialInvoice->value])]
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(writable: false)]
    private InvoiceTypeCode $invoiceTypeCode = InvoiceTypeCode::CommercialInvoice;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['invoice_api:read', 'searchable'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'invoice_id', type: Types::STRING, length: 255)]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'searchable'])]
    private string $invoiceId = '';

    #[ORM\Column(name: 'uuid', type: Types::STRING, length: 36)]
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(writable: false)]
    private ?string $uuid = null;

    #[ApiProperty(
        example: '/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6',
        iris: ['https://schema.org/Organization']
    )]
    #[ORM\ManyToOne(targetEntity: Client::class, cascade: ['persist'], inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'searchable'])]
    private ?Client $client = null;

    #[ORM\Column(name: 'balance_amount', type: BigIntegerType::NAME)]
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    private BigNumber $balance;

    #[ORM\Column(name: 'due', type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\Type(type: DateTimeInterface::class)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private ?DateTimeInterface $due = null;

    #[ORM\Column(name: 'invoice_date', type: Types::DATE_IMMUTABLE, nullable: false)]
    #[Assert\Type(type: DateTimeInterface::class)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private DateTimeInterface $invoiceDate;

    #[ORM\Column(name: 'paid_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\Type(type: DateTimeInterface::class)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private ?DateTimeInterface $paidDate = null;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'invoice', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    #[ApiProperty(example: ['/api/payments/3fa85f64-5717-4562-b3fc-2c963f66afa6'])]
    private Collection $payments;

    #[ORM\OneToOne(targetEntity: Quote::class, inversedBy: 'invoice')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(example: '/api/quotes/3fa85f64-5717-4562-b3fc-2c963f66afa6')]
    private ?Quote $quote = null;

    #[ORM\ManyToOne(targetEntity: RecurringInvoice::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'recurring_invoice_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?RecurringInvoice $recurringInvoice = null;

    /**
     * @var Collection<int, Line>
     */
    #[ORM\OneToMany(targetEntity: Line::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'invoice.lines.min')]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private Collection $lines;

    /**
     * @var Collection<int, Contact>
     */
    #[ApiProperty(
        writableLink: true,
        example: ['/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6/contact/3fa85f64-5717-4562-b3fc-2c963f66afa6'],
    )]
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'invoices')]
    #[ORM\JoinTable(name: 'invoice_contact')]
    #[Assert\Count(min: 1, minMessage: 'invoice.users.min')]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private Collection $users;

    /**
     * @var Collection<int, InvoiceTax>
     */
    #[ORM\OneToMany(targetEntity: InvoiceTax::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private Collection $invoiceTaxes;

    public function __construct()
    {
        parent::__construct();

        $this->payments = new ArrayCollection();
        $this->lines = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->invoiceTaxes = new ArrayCollection();
        $this->balance = BigInteger::zero();
        $this->invoiceDate = CarbonImmutable::now();
        $this->setUuid(Uuid::v7());
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getStatus(): ?InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusValue(): ?string
    {
        return $this->status?->value;
    }

    public function setStatusValue(string $status): static
    {
        $this->status = InvoiceStatus::from($status);

        return $this;
    }

    public function getInvoiceTypeCode(): InvoiceTypeCode
    {
        return $this->invoiceTypeCode;
    }

    public function setInvoiceTypeCode(InvoiceTypeCode $invoiceTypeCode): static
    {
        $this->invoiceTypeCode = $invoiceTypeCode;

        return $this;
    }

    public function getUuid(): Uuid
    {
        return Uuid::fromString($this->uuid);
    }

    /**
     * Placeholder for the API docs; {@see \SolidInvoice\CoreBundle\Serializer\Normalizer\BillingLinksNormalizer} sets the value.
     */
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(description: 'Absolute URL of the public invoice page, the link sent to the client', writable: false, schema: ['type' => 'string', 'format' => 'uri'])]
    public function getPublicUrl(): ?string
    {
        return null;
    }

    /**
     * Placeholder for the API docs; {@see \SolidInvoice\CoreBundle\Serializer\Normalizer\BillingLinksNormalizer} sets the value.
     */
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(description: 'Absolute URL of the public PDF of the invoice', writable: false, schema: ['type' => 'string', 'format' => 'uri'])]
    public function getPdfUrl(): ?string
    {
        return null;
    }

    /**
     * Placeholder for the API docs; {@see \SolidInvoice\CoreBundle\Serializer\Normalizer\BillingLinksNormalizer} sets the value.
     */
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(description: 'Absolute URL where the client pays the invoice online; null when the company has no enabled online payment method', writable: false, schema: ['type' => ['string', 'null'], 'format' => 'uri'])]
    public function getPayUrl(): ?string
    {
        return null;
    }

    public function setUuid(Uuid $uuid): self
    {
        $this->uuid = $uuid->toString();
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

    public function getBalance(): BigNumber
    {
        return $this->balance;
    }

    /**
     * @throws MathException
     */
    public function setBalance(BigNumber | float | int | string $balance): self
    {
        $this->balance = BigNumber::of($balance);

        return $this;
    }

    public function getDue(): ?DateTimeInterface
    {
        return $this->due;
    }

    public function setDue(?DateTimeInterface $due): self
    {
        $this->due = $due;
        $this->syncDue();

        return $this;
    }

    public function getPaidDate(): ?DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(?DateTimeImmutable $paidDate): self
    {
        $this->paidDate = $paidDate;
        return $this;
    }

    public function addLine(LineInterface $line): self
    {
        assert($line instanceof Line);
        $this->lines->add($line);
        $line->setInvoice($this);
        if (isset($this->company)) {
            $line->setCompany($this->getCompany());
        }

        $this->compactLinePositions($this->lines);

        return $this;
    }

    public function removeLine(Line $line): self
    {
        $this->lines->removeElement($line);
        $line->setInvoice(null);

        $this->compactLinePositions($this->lines);

        return $this;
    }

    /**
     * @return Collection<int, Line>
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
            $line->setInvoice($this);
        }

        $this->compactLinePositions($this->lines);
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments->add($payment);
        $payment->setInvoice($this);
        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);
        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function retainsFrozenCurrency(): bool
    {
        return ! $this->payments->isEmpty();
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(Quote $quote): self
    {
        $this->quote = $quote;
        $quote->setInvoice($this);
        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(Contact $user): self
    {
        if (! $this->users->contains($user)) {
            $this->users->add($user);

            if (! $user->getInvoices()->contains($this)) {
                $user->addInvoice($this);
            }
        }

        return $this;
    }

    public function removeUser(Contact $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeInvoice($this);
        }

        return $this;
    }

    public function __clone()
    {
        $originalLines = $this->lines;
        $this->lines = new ArrayCollection();
        foreach ($originalLines as $line) {
            $this->addLine(clone $line);
        }

        $this->setUuid(Uuid::v7());
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getInvoiceDate(): DateTimeInterface
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(DateTimeInterface $invoiceDate): self
    {
        $this->invoiceDate = $invoiceDate;
        $this->syncDue();

        return $this;
    }

    #[Groups(['searchable'])]
    #[SerializedName('created')]
    public function getCreatedTimestamp(): int
    {
        return $this->created->getTimestamp();
    }

    /**
     * @return list<string>
     */
    #[Groups(['searchable'])]
    public function getLineDescriptions(): array
    {
        $text = [];

        foreach ($this->lines as $line) {
            $text[] = $line->getName();
            $text[] = (string) $line->getDescription();
        }

        return array_values(array_filter($text, static fn (string $value) => $value !== ''));
    }

    public function __toString(): string
    {
        return $this->invoiceId;
    }

    public function getRecurringInvoice(): ?RecurringInvoice
    {
        return $this->recurringInvoice;
    }

    public function setRecurringInvoice(?RecurringInvoice $recurringInvoice): void
    {
        $this->recurringInvoice = $recurringInvoice;
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
            $invoiceTax->setInvoice($this);
        }

        return $this;
    }

    public function removeInvoiceTax(InvoiceTax $invoiceTax): self
    {
        if ($this->invoiceTaxes->removeElement($invoiceTax) && $invoiceTax->getInvoice() === $this) {
            $invoiceTax->setInvoice(null);
        }

        return $this;
    }

    #[Override]
    public function setPaymentTerms(PaymentTerms $paymentTerms): static
    {
        parent::setPaymentTerms($paymentTerms);
        $this->syncDue();

        return $this;
    }

    /**
     * A preset term owns the due date, so it wins whatever order the setters run in.
     */
    private function syncDue(): void
    {
        if ($this->paymentTerms !== PaymentTerms::Custom) {
            $this->due = $this->paymentTerms->dueDate($this->invoiceDate);
        }
    }

    #[Groups(['invoice_api:read'])]
    #[Context([DateTimeNormalizer::TIMEZONE_KEY => 'UTC'])]
    #[SerializedName('created')]
    public function getApiCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    #[Groups(['invoice_api:read'])]
    #[Context([DateTimeNormalizer::TIMEZONE_KEY => 'UTC'])]
    #[SerializedName('updated')]
    public function getApiUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    #[Groups(['invoice_api:read'])]
    #[SerializedName('currencyCode')]
    #[ApiProperty(writable: false, example: 'USD')]
    public function getApiCurrencyCode(): ?string
    {
        return $this->resolveCurrencyCode();
    }
}
