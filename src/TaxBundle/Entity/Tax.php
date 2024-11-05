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

namespace SolidInvoice\TaxBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Tax::TABLE_NAME)]
#[ORM\Entity(repositoryClass: TaxRepository::class)]
#[UniqueEntity('name')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: [
        'groups' => ['tax_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['tax_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
class Tax implements Stringable
{
    final public const TABLE_NAME = 'tax_rates';

    use TimeStampable;
    use CompanyAware;

    final public const TYPE_INCLUSIVE = 'Inclusive';

    final public const TYPE_EXCLUSIVE = 'Exclusive';

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['tax_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 32)]
    #[Assert\NotBlank]
    #[Groups(['tax_api:read', 'tax_api:write'])]
    private ?string $name = null;

    #[ORM\Column(name: 'rate', type: Types::FLOAT, precision: 4)]
    #[Assert\Type('float')]
    #[Assert\NotBlank]
    #[Groups(['tax_api:read', 'tax_api:write'])]
    private ?float $rate = null;

    #[ORM\Column(name: 'tax_type', type: Types::STRING, length: 32)]
    #[Assert\NotBlank]
    #[Groups(['tax_api:read', 'tax_api:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => [
                'type' => 'string',
                'enum' => ['inclusive', 'exclusive'],
            ],
        ],
        jsonSchemaContext: [
            'type' => [
                'type' => 'string',
                'enum' => ['inclusive', 'exclusive'],
            ],
        ]
    )]
    private ?string $type = null;

    /**
     * @var Collection<int, Line>
     */
    #[ORM\OneToMany(mappedBy: 'tax', targetEntity: InvoiceLine::class)]
    private Collection $invoiceLines;

    /**
     * @var Collection<int, QuoteLine>
     */
    #[ORM\OneToMany(mappedBy: 'tax', targetEntity: QuoteLine::class)]
    private Collection $quoteLines;

    public function __construct()
    {
        $this->invoiceLines = new ArrayCollection();
        $this->quoteLines = new ArrayCollection();
    }

    /**
     * @return array{Inclusive: string, Exclusive: string}
     */
    public static function getTypes(): array
    {
        $types = [
            self::TYPE_INCLUSIVE,
            self::TYPE_EXCLUSIVE,
        ];

        return array_combine($types, $types);
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Line>
     */
    public function getInvoiceLines(): Collection
    {
        return $this->invoiceLines;
    }

    /**
     * @param Line[] $invoiceLines
     */
    public function setInvoiceLines(array $invoiceLines): self
    {
        $this->invoiceLines = new ArrayCollection($invoiceLines);

        return $this;
    }

    /**
     * @return Collection<int, QuoteLine>
     */
    public function getQuoteLines(): Collection
    {
        return $this->quoteLines;
    }

    /**
     * @param QuoteLine[] $quoteLines
     */
    public function setQuoteLines(array $quoteLines): self
    {
        $this->quoteLines = new ArrayCollection($quoteLines);

        return $this;
    }

    public function __toString(): string
    {
        $type = self::TYPE_INCLUSIVE === $this->type ? 'inc' : 'exl';

        return "{$this->rate}% {$this->name} ({$type})";
    }
}
