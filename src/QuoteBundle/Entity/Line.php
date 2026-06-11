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

namespace SolidInvoice\QuoteBundle\Entity;

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
use Brick\Math\Exception\RoundingNecessaryException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ApiBundle\State\Processor\QuoteLinePersistProcessor;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\QuoteBundle\Repository\LineRepository;
use SolidInvoice\TaxBundle\Entity\LineTax;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Line::TABLE_NAME)]
#[ORM\Entity(repositoryClass: LineRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    uriTemplate: '/quotes/{quoteId}/lines',
    shortName: 'QuoteLine',
    operations: [new GetCollection(), new Post(processor: QuoteLinePersistProcessor::class)],
    uriVariables: [
        'quoteId' => new Link(
            fromProperty: 'lines',
            fromClass: Quote::class,
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
    uriTemplate: '/quotes/{quoteId}/line/{id}',
    shortName: 'QuoteLine',
    operations: [new Get(), new Patch(), new Delete()],
    uriVariables: [
        'quoteId' => new Link(
            fromProperty: 'lines',
            fromClass: Quote::class,
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
    final public const string TABLE_NAME = 'quote_lines';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['quote_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'description', type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private ?string $description = null;

    #[ORM\Column(name: 'price_amount', type: BigIntegerType::NAME)]
    #[Assert\NotBlank]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    private BigNumber $price;

    #[ORM\Column(name: 'qty', type: Types::FLOAT)]
    #[Assert\NotBlank]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private ?float $qty = 1;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[ApiProperty(
        writable: false,
        writableLink: false,
        example: '/api/quotes/3fa85f64-5717-4562-b3fc-2c963f66afa6'
    )]
    private ?Quote $quote = null;

    /**
     * @var Collection<int, LineTax>
     */
    #[ORM\OneToMany(mappedBy: 'quoteLine', targetEntity: LineTax::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private Collection $taxes;

    #[ORM\Column(name: 'total_amount', type: BigIntegerType::NAME)]
    #[Groups(['quote_api:read'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    private BigNumber $total;

    public function __construct()
    {
        $this->total = BigDecimal::zero();
        $this->price = BigDecimal::zero();
        $this->taxes = new ArrayCollection();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function setDescription(?string $description): static
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
    public function setPrice(BigNumber|int|string $price): static
    {
        $this->price = BigNumber::of($price);

        return $this;
    }

    public function getPrice(): BigNumber
    {
        return $this->price;
    }

    public function setQty(float $qty): static
    {
        $this->qty = $qty;

        return $this;
    }

    public function getQty(): ?float
    {
        return $this->qty;
    }

    public function setQuote(?Quote $quote = null): static
    {
        $this->quote = $quote;

        return $this;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    /**
     * @throws MathException
     */
    public function setTotal(BigNumber|int|string $total): static
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
            $lineTax->setQuoteLine($this);
        }

        return $this;
    }

    public function removeTax(LineTax $lineTax): static
    {
        if ($this->taxes->removeElement($lineTax) && $lineTax->getQuoteLine() === $this) {
            $lineTax->setQuoteLine(null);
        }

        return $this;
    }

    /**
     * @throws MathException
     * @throws RoundingNecessaryException
     */
    #[ORM\PrePersist]
    public function updateTotal(): static
    {
        $this->total = $this->getPrice()->toBigDecimal()->multipliedBy($this->qty !== null ? (string) $this->qty : 1);

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->description;
    }
}
