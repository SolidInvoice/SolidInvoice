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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Enum\InvoiceLineType;
use SolidInvoice\InvoiceBundle\Repository\LineRepository;
use SolidInvoice\TaxBundle\Entity\Tax;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Line::TABLE_NAME)]
#[ORM\Entity(repositoryClass: LineRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', enumType: InvoiceLineType::class)]
#[ORM\DiscriminatorMap(['invoice' => Line::class, 'recurring_invoice' => RecurringInvoiceLine::class])]
#[ApiResource(
    uriTemplate: '/invoices/{invoiceId}/lines',
    shortName: 'InvoiceLine',
    operations: [new GetCollection(), new Post()],
    uriVariables: [
        'invoiceId' => new Link(
            fromProperty: 'lines',
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
    uriTemplate: '/invoices/{invoiceId}/line/{id}',
    shortName: 'InvoiceLine',
    operations: [new Get(), new Patch(), new Delete()],
    uriVariables: [
        'invoiceId' => new Link(
            fromProperty: 'lines',
            fromClass: Invoice::class,
        ),
        'id' => new Link(
            fromClass: Line::class,
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
class Line implements LineInterface, Stringable
{
    final public const TABLE_NAME = 'invoice_lines';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read'])]
    protected ?Ulid $id = null;

    #[ORM\Column(name: 'description', type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected ?string $description = null;

    #[ORM\Column(name: 'price_amount', type: BigIntegerType::NAME)]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $price;

    #[ORM\Column(name: 'qty', type: Types::FLOAT)]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected ?float $qty = 1;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'lines')]
    #[ApiProperty(
        writable: false,
        writableLink: false,
        example: '/api/invoices/3fa85f64-5717-4562-b3fc-2c963f66afa6'
    )]
    protected ?Invoice $invoice = null;

    #[ORM\ManyToOne(targetEntity: Tax::class, inversedBy: 'invoiceLines')]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    #[ApiProperty(example: '/api/tax/3fa85f64-5717-4562-b3fc-2c963f66afa6')]
    protected ?Tax $tax = null;

    #[ORM\Column(name: 'total_amount', type: BigIntegerType::NAME)]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $total;

    public function __construct()
    {
        $this->total = BigInteger::zero();
        $this->price = BigInteger::zero();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function setDescription(string $description): LineInterface
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @throws MathException
     */
    public function setPrice(BigNumber|float|int|string $price): LineInterface
    {
        $this->price = BigNumber::of($price);

        return $this;
    }

    public function getPrice(): BigNumber
    {
        return $this->price;
    }

    public function setQty(float $qty): LineInterface
    {
        $this->qty = $qty;

        return $this;
    }

    public function getQty(): ?float
    {
        return $this->qty;
    }

    public function setInvoice(?Invoice $invoice): LineInterface
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    /**
     * @throws MathException
     */
    public function setTotal(BigNumber|float|int|string $total): LineInterface
    {
        $this->total = BigNumber::of($total);

        return $this;
    }

    public function getTotal(): BigNumber
    {
        return $this->total;
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): LineInterface
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @throws MathException
     */
    #[ORM\PrePersist]
    public function updateTotal(): void
    {
        $this->total = $this->getPrice()->toBigDecimal()->multipliedBy($this->qty);
    }

    public function __toString(): string
    {
        return (string) $this->description;
    }
}
