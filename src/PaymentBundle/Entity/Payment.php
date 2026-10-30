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

namespace SolidInvoice\PaymentBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Override;
use Payum\Core\Model\Payment as BasePayment;
use SolidInvoice\ApiBundle\DTO\RecordPaymentInput;
use SolidInvoice\ApiBundle\State\Processor\RecordPaymentProcessor;
use SolidInvoice\ApiBundle\State\Processor\VoidPaymentProcessor;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Exception\UnexpectedTypeException;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Traversable;

#[ORM\Table(name: Payment::TABLE_NAME)]
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact', 'invoice' => 'exact', 'client' => 'exact'])]
#[ApiFilter(DateFilter::class, properties: ['completed', 'created', 'updated'])]
#[ApiFilter(OrderFilter::class, properties: ['completed', 'created', 'updated'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/invoices/{invoiceId}/payments',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'payments',
                    fromClass: Invoice::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/invoices/{invoiceId}/payments',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'payments',
                    fromClass: Invoice::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['payment_api:read', 'payment_api:record'],
                AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
            ],
            input: RecordPaymentInput::class,
            // The `payments` link would otherwise have API Platform read the invoice's existing
            // payment. A create has nothing to read; the processor loads the invoice itself.
            read: false,
            processor: RecordPaymentProcessor::class,
            extraProperties: ['idempotent' => true],
        ),
        new GetCollection(
            uriTemplate: '/clients/{clientId}/payments',
            uriVariables: [
                'clientId' => new Link(
                    fromProperty: 'payments',
                    fromClass: Client::class,
                ),
            ],
        ),
        new Get(),
        new GetCollection(),
        new Post(
            uriTemplate: '/payments/{id}/void',
            status: 200,
            input: false,
            read: true,
            processor: VoidPaymentProcessor::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['payment_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    graphQlOperations: [],
)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'company', inversedBy: 'payments')])]
class Payment extends BasePayment
{
    final public const string TABLE_NAME = 'payments';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['searchable', 'payment_api:read'])]
    protected ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['searchable', 'payment_api:read'])]
    private ?Invoice $invoice = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(name: 'client', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['searchable', 'payment_api:read'])]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: PaymentMethod::class, inversedBy: 'payments')]
    #[Groups(['payment_api:read'])]
    private ?PaymentMethod $method = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25, enumType: PaymentStatus::class)]
    #[Groups(['searchable', 'payment_api:read'])]
    private ?PaymentStatus $status = null;

    #[ORM\Column(name: 'message', type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'completed', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\DateTime]
    private ?DateTimeImmutable $completed = null;

    #[ORM\Column(name: 'reference', type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['searchable', 'payment_api:read'])]
    private ?string $reference = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[Groups(['searchable', 'payment_api:read'])]
    private ?string $notes = null;

    /**
     * Not persisted: what recording this payment added to the client's credit.
     */
    private ?BigNumber $creditAdded = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getMethod(): ?PaymentMethod
    {
        return $this->method;
    }

    public function setMethod(PaymentMethod $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getStatus(): ?PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param array<string, string>|Traversable<string> $details
     *
     * @throws UnexpectedTypeException
     */
    #[Override]
    public function setDetails($details): self
    {
        if ($details instanceof Traversable) {
            $details = iterator_to_array($details);
        }

        if (! is_array($details)) {
            throw new UnexpectedTypeException((string) $details, 'array or \Traversable');
        }

        $this->details = $details;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getCompleted(): ?DateTimeImmutable
    {
        return $this->completed;
    }

    public function setCompleted(DateTimeInterface $completed): self
    {
        // The column is immutable; DBAL 4 rejects mutable values, so normalise here.
        $this->completed = $completed instanceof DateTimeImmutable ? $completed : DateTimeImmutable::createFromInterface($completed);

        return $this;
    }

    #[Override]
    public function getClientId(): ?string
    {
        $client = $this->getClient();

        return $client instanceof Client && $client->getId() instanceof Ulid ? $client->getId()->toString() : null;
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

    /**
     * Only a settled payment against an invoice is something a client can be given a receipt for.
     */
    public function hasReceipt(): bool
    {
        return $this->status === PaymentStatus::Captured && $this->invoice instanceof Invoice;
    }

    /**
     * Receipts are not stored, so the number is derived: the invoice number and this payment's place
     * among the invoice's payments. ULIDs sort by creation and a payment is never deleted on its own,
     * so the number does not change once given out.
     */
    public function getReceiptNumber(): string
    {
        if (! $this->invoice instanceof Invoice || ! $this->id instanceof Ulid) {
            return (string) $this->id;
        }

        $position = 1;

        foreach ($this->invoice->getPayments() as $payment) {
            $id = $payment->getId();

            if ($id instanceof Ulid && $id->compare($this->id) < 0) {
                ++$position;
            }
        }

        return sprintf('%s-%d', $this->invoice->getInvoiceId(), $position);
    }

    public function getAmount(): Money
    {
        return new Money($this->getTotalAmount(), new Currency($this->getCurrencyCode()));
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    #[Groups(['searchable'])]
    #[SerializedName('total')]
    public function getSearchableTotal(): float
    {
        return (int) $this->getTotalAmount() / 100;
    }

    #[Groups(['searchable', 'payment_api:read'])]
    #[SerializedName('currencyCode')]
    public function getSearchableCurrencyCode(): ?string
    {
        return $this->getCurrencyCode() ?: null;
    }

    #[Groups(['payment_api:read'])]
    #[Context([DateTimeNormalizer::TIMEZONE_KEY => 'UTC'])]
    #[SerializedName('created')]
    public function getApiCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    #[Groups(['payment_api:read'])]
    #[Context([DateTimeNormalizer::TIMEZONE_KEY => 'UTC'])]
    #[SerializedName('updated')]
    public function getApiUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * The amount in the minor unit, for the API's money normalizer.
     */
    #[ApiProperty(writable: false, openapiContext: ['type' => 'number'], jsonSchemaContext: ['type' => 'number'])]
    #[Groups(['payment_api:read'])]
    #[SerializedName('amount')]
    public function getApiAmount(): BigInteger
    {
        return BigInteger::of($this->getTotalAmount() ?? 0);
    }

    #[Groups(['payment_api:read'])]
    #[Context([DateTimeNormalizer::TIMEZONE_KEY => 'UTC'])]
    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->completed;
    }

    #[ApiProperty(writable: false, openapiContext: ['type' => 'number'], jsonSchemaContext: ['type' => 'number'])]
    #[Groups(['payment_api:record'])]
    public function getCreditAdded(): BigNumber
    {
        return $this->creditAdded ?? BigInteger::zero();
    }

    public function setCreditAdded(BigNumber $creditAdded): self
    {
        $this->creditAdded = $creditAdded;

        return $this;
    }
}
