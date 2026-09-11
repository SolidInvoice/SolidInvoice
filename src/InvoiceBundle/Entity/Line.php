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
use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ApiBundle\Serializer\Normalizer\BigIntegerNormalizer;
use SolidInvoice\ApiBundle\State\Processor\InvoiceLinePersistProcessor;
use SolidInvoice\CoreBundle\Billing\LineName;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Enum\InvoiceLineType;
use SolidInvoice\InvoiceBundle\Repository\LineRepository;
use SolidInvoice\TaxBundle\Entity\LineTax;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use function trim;

#[ORM\Table(name: Line::TABLE_NAME)]
#[ORM\Entity(repositoryClass: LineRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', enumType: InvoiceLineType::class)]
#[ORM\DiscriminatorMap(['invoice' => Line::class, 'recurring_invoice' => RecurringInvoiceLine::class])]
#[ApiResource(
    shortName: 'InvoiceLine',
    operations: [
        new GetCollection(
            uriTemplate: '/invoices/{invoiceId}/lines',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: Invoice::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/invoices/{invoiceId}/lines',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: Invoice::class,
                ),
            ],
            // Without this, the `lines` link makes API Platform read the owner's existing
            // line and deserialize into it, so a second post overwrites the first instead
            // of adding one. A create has nothing to read.
            read: false,
            processor: InvoiceLinePersistProcessor::class,
        ),
        new Get(
            uriTemplate: '/invoices/{invoiceId}/line/{id}',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: Invoice::class,
                ),
                'id' => new Link(
                    fromClass: Line::class,
                ),
            ],
        ),
        new Patch(
            uriTemplate: '/invoices/{invoiceId}/line/{id}',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: Invoice::class,
                ),
                'id' => new Link(
                    fromClass: Line::class,
                ),
            ],
        ),
        new Delete(
            uriTemplate: '/invoices/{invoiceId}/line/{id}',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: Invoice::class,
                ),
                'id' => new Link(
                    fromClass: Line::class,
                ),
            ],
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'company', inversedBy: 'invoiceLines')])]
class Line implements LineInterface, Stringable
{
    final public const string TABLE_NAME = 'invoice_lines';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read'])]
    protected ?Ulid $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: LineName::MAX_LENGTH, options: ['default' => ''])]
    #[Assert\NotBlank]
    #[Assert\Length(max: LineName::MAX_LENGTH)]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected string $name = '';

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected ?string $description = null;

    /**
     * {@see LineInterface::UNPLACED} until an owner places the line, which is what makes an
     * added line an append.
     *
     * Read over the API, never written. A position is not something a line carries but the
     * index its owner gave it, so the way to change one is to send the owner's `lines` in the
     * order you want them; setting it on a single line is the one way to end up with the
     * duplicates and gaps that `0..n-1` exists to rule out.
     */
    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read'])]
    #[ApiProperty(writable: false)]
    protected int $position = LineInterface::UNPLACED;

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

    #[ORM\Column(name: 'qty', type: QuantityType::NAME, precision: QuantityType::PRECISION, scale: QuantityType::SCALE)]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    #[Context(
        normalizationContext: [BigIntegerNormalizer::MONETARY => false],
        denormalizationContext: [BigIntegerNormalizer::MONETARY => false],
    )]
    #[ApiProperty(
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    protected BigNumber $qty;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[ApiProperty(
        writable: false,
        writableLink: false,
        example: '/api/invoices/3fa85f64-5717-4562-b3fc-2c963f66afa6'
    )]
    protected ?Invoice $invoice = null;

    /**
     * @var Collection<int, LineTax>
     */
    #[ORM\OneToMany(targetEntity: LineTax::class, mappedBy: 'invoiceLine', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected Collection $taxes;

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
        $this->total = BigDecimal::zero();
        $this->price = BigDecimal::zero();
        $this->qty = BigDecimal::one();
        $this->taxes = new ArrayCollection();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Deriving the name here, rather than in a lifecycle callback, is what keeps a caller
     * that only sends a description valid: `name` is `NotBlank`, so it has to hold a value
     * by the time the object is validated, which is long before it is persisted.
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        if ($this->name !== '' || ($description ?? '') === '') {
            return $this;
        }

        $derived = LineName::fromDescription($description);

        if ($derived === '') {
            return $this;
        }

        $this->name = $derived;
        // The description is dropped only when the name is all of it — whitespace aside.
        // Anything longer stays, so nothing the caller wrote is lost.
        $this->description = trim($description) === $derived ? null : $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * A line attached with {@see self::setInvoice()} rather than {@see Invoice::addLine()} is
     * never renumbered, so it would store the sentinel. Zero, because a line its owner never
     * placed has no established slot to keep.
     */
    #[ORM\PrePersist]
    public function placeUnplacedLine(): void
    {
        if ($this->position === LineInterface::UNPLACED) {
            $this->position = 0;
        }
    }

    /**
     * A line deleted through its own endpoint — `DELETE /invoices/{id}/line/{id}` — never passes
     * through {@see Invoice::removeLine()}, so the lines after it would keep their old numbers
     * and leave the gap the deleted line used to fill. Routing the removal back through the
     * owner closes it.
     *
     * PreRemove fires from `EntityManager::remove()`, before the flush works out what changed,
     * so the renumbered siblings go out in the same flush as the delete.
     */
    #[ORM\PreRemove]
    public function detachFromOwner(): void
    {
        $this->invoice?->removeLine($this);
    }

    /**
     * @throws MathException
     */
    public function setPrice(BigNumber | int | string $price): static
    {
        $this->price = BigNumber::of($price);

        return $this;
    }

    public function getPrice(): BigNumber
    {
        return $this->price;
    }

    /**
     * @throws MathException
     */
    public function setQty(BigNumber | int | string $qty): static
    {
        // Normalized on the way in, not on the way out: updateTotal() runs before DBAL
        // converts the quantity, so the total has to be computed from the same value the
        // column will hold. See QuantityType::normalize().
        $this->qty = QuantityType::normalize(BigNumber::of($qty));

        return $this;
    }

    public function getQty(): BigNumber
    {
        return $this->qty;
    }

    public function setInvoice(?Invoice $invoice): static
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
    public function setTotal(BigNumber | int | string $total): static
    {
        $this->total = BigNumber::of($total);

        return $this;
    }

    public function getTotal(): BigNumber
    {
        return $this->total;
    }

    /**
     * @return Collection<int, LineTax>
     */
    public function getTaxes(): Collection
    {
        return $this->taxes;
    }

    public function addTax(LineTax $lineTax): static
    {
        if (! $this->taxes->contains($lineTax)) {
            $this->taxes->add($lineTax);
            $lineTax->setInvoiceLine($this);
        }

        return $this;
    }

    public function removeTax(LineTax $lineTax): static
    {
        if ($this->taxes->removeElement($lineTax) && $lineTax->getInvoiceLine() === $this) {
            $lineTax->setInvoiceLine(null);
        }

        return $this;
    }

    /**
     * @throws MathException
     */
    #[ORM\PrePersist]
    public function updateTotal(): static
    {
        $this->total = $this->getPrice()->toBigDecimal()->multipliedBy($this->qty->toBigDecimal());

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
