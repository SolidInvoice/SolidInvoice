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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use SolidInvoice\TaxBundle\Validator\Constraints\IncompatibleTaxConfiguration;
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
#[IncompatibleTaxConfiguration]
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
    final public const string TABLE_NAME = 'tax_rates';

    use TimeStampable;
    use CompanyAware;

    final public const string TYPE_INCLUSIVE = 'Inclusive';

    final public const string TYPE_EXCLUSIVE = 'Exclusive';

    final public const string TYPE_FLAT_RATE = 'Flat Rate';

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
            'type' => 'string',
            'enum' => [self::TYPE_INCLUSIVE, self::TYPE_EXCLUSIVE, self::TYPE_FLAT_RATE],
        ],
        jsonSchemaContext: [
            'type' => 'string',
            'enum' => [self::TYPE_INCLUSIVE, self::TYPE_EXCLUSIVE, self::TYPE_FLAT_RATE],
        ]
    )]
    private ?string $type = null;

    #[ORM\Column(name: 'category', type: Types::STRING, length: 32, enumType: TaxCategory::class, options: ['default' => TaxCategory::Standard->value])]
    #[Groups(['tax_api:read', 'tax_api:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => [
                TaxCategory::Standard->value,
                TaxCategory::ZeroRated->value,
                TaxCategory::Exempt->value,
                TaxCategory::OutOfScope->value,
                TaxCategory::ReverseCharge->value,
            ],
        ],
        jsonSchemaContext: [
            'type' => 'string',
            'enum' => [
                TaxCategory::Standard->value,
                TaxCategory::ZeroRated->value,
                TaxCategory::Exempt->value,
                TaxCategory::OutOfScope->value,
                TaxCategory::ReverseCharge->value,
            ],
        ]
    )]
    private TaxCategory $category = TaxCategory::Standard;

    #[ORM\Column(name: 'compound', type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['tax_api:read', 'tax_api:write'])]
    private bool $compound = false;

    /**
     * @return array{Inclusive: string, Exclusive: string}
     */
    public static function getTypes(): array
    {
        $types = [
            self::TYPE_INCLUSIVE,
            self::TYPE_EXCLUSIVE,
            self::TYPE_FLAT_RATE,
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

    public function getCategory(): TaxCategory
    {
        return $this->category;
    }

    public function setCategory(TaxCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function isCompound(): bool
    {
        return $this->compound;
    }

    public function setCompound(bool $compound): self
    {
        $this->compound = $compound;

        return $this;
    }

    public function __toString(): string
    {
        [$rate, $type] = match ($this->getType()) {
            self::TYPE_INCLUSIVE => [$this->rate . '%', 'inc'],
            self::TYPE_EXCLUSIVE => [$this->rate . '%', 'exl'],
            self::TYPE_FLAT_RATE => [$this->rate, 'flat'],
            default => [$this->rate, 'n/a'],
        };

        return sprintf('%s %s (%s)', $rate, $this->name, $type);
    }
}
