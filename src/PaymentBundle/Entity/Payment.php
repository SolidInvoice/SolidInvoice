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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Payum\Core\Model\Payment as BasePayment;
use Payum\Core\Model\PaymentInterface;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Exception\UnexpectedTypeException;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Constraints as Assert;
use Traversable;

#[ORM\Table(name: Payment::TABLE_NAME)]
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ApiResource(
    uriTemplate: '/invoices/{invoiceId}/payments',
    operations: [new GetCollection()],
    uriVariables: [
        'invoiceId' => new Link(
            fromProperty: 'payments',
            fromClass: Invoice::class,
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
#[ApiResource(
    uriTemplate: '/invoices/{invoiceId}/payment/{id}',
    operations: [new Get()],
    uriVariables: [
        'invoiceId' => new Link(
            fromProperty: 'payments',
            fromClass: Invoice::class,
        ),
        'id' => new Link(
            fromClass: Payment::class,
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
/*

Having multiple ApiResource attributes with different uriTemplates on the same class
with the same operation does not work as expected in ApiPlatform.
These attributes are kept here as a reference to try and get this working in the future.

#[ApiResource(
    uriTemplate: '/clients/{clientId}/payments',
    operations: [new GetCollection()],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'payments',
            fromClass: Client::class,
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/payment/{id}',
    operations: [new Get(), new Patch(), new Delete()],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'payments',
            fromClass: Client::class,
        ),
        'id' => new Link(
            fromClass: Payment::class,
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]*/
class Payment extends BasePayment implements PaymentInterface
{
    final public const TABLE_NAME = 'payments';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    protected ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'payments')]
    private ?Invoice $invoice = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(name: 'client', fieldName: 'client')]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: PaymentMethod::class, inversedBy: 'payments')]
    // #[Serialize\Groups(['payment_api', 'client_api'])]
    private ?PaymentMethod $method = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25)]
    // #[Serialize\Groups(['payment_api', 'client_api'])]
    private ?string $status = null;

    #[ORM\Column(name: 'message', type: Types::TEXT, nullable: true)]
    // #[Serialize\Groups(['payment_api', 'client_api'])]
    private ?string $message = null;

    #[ORM\Column(name: 'completed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\DateTime]
    // #[Serialize\Groups(['payment_api', 'client_api'])]
    private ?DateTimeInterface $completed = null;

    public function getId(): ?UuidInterface
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param array<string, string>|Traversable<string> $details
     *
     * @throws UnexpectedTypeException
     */
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

    public function getCompleted(): ?DateTimeInterface
    {
        return $this->completed;
    }

    public function setCompleted(DateTimeInterface $completed): self
    {
        $this->completed = $completed;

        return $this;
    }

    public function getClientId(): ?string
    {
        $client = $this->getClient();

        return $client instanceof Client && $client->getId() instanceof UuidInterface ? $client->getId()->toString() : null;
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

    #[ApiProperty(
        openapiContext: [
            'type' => 'object',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'amount' => [
                        'type' => 'number',
                    ],
                    'currency' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ],
        jsonSchemaContext: [
            'type' => 'object',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'amount' => [
                        'type' => 'number',
                    ],
                    'currency' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]
    )]
    public function getAmount(): Money
    {
        return new Money($this->getTotalAmount(), new Currency($this->getCurrencyCode()));
    }
}
