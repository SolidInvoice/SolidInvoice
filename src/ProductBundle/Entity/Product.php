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

namespace SolidInvoice\ProductBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Brick\Math\BigDecimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Billing\LineName;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\TaxBundle\Entity\Tax;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute as Serialize;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see \SolidInvoice\ProductBundle\Tests\Entity\ProductTest
 */
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['name'])]
#[ApiResource(
    types: ['https://schema.org/Product'],
    operations: [
        new Get(),
        new Post(),
        new GetCollection(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: [
        'groups' => ['product_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
        AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
    ],
    denormalizationContext: [
        'groups' => ['product_api:write'],
    ],
    validationContext: [
        'groups' => ['Default', 'api'],
    ],
)]
#[ORM\Table(name: Product::TABLE_NAME)]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product implements Stringable
{
    final public const string TABLE_NAME = 'products';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Serialize\Groups(['product_api:read', 'searchable'])]
    private ?Ulid $id = null;

    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[ORM\Column(name: 'name', type: Types::STRING, length: LineName::MAX_LENGTH)]
    #[Assert\NotBlank]
    #[Assert\Length(max: LineName::MAX_LENGTH)]
    #[Serialize\Groups(['product_api:read', 'product_api:write', 'searchable'])]
    private string $name = '';

    #[ApiProperty(iris: ['https://schema.org/description'])]
    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[Serialize\Groups(['product_api:read', 'product_api:write', 'searchable'])]
    private ?string $description = null;

    /**
     * A major-unit decimal amount with no currency, held as a string so no precision is lost
     * between the column and the {@see BigDecimal} the accessors expose.
     */
    #[ORM\Column(name: 'price', type: Types::DECIMAL, precision: 19, scale: 6)]
    #[Assert\PositiveOrZero]
    #[Serialize\Groups(['product_api:read', 'product_api:write', 'searchable'])]
    private string $price = '0';

    #[ORM\Column(name: 'quantity', type: QuantityType::NAME, precision: QuantityType::PRECISION, scale: QuantityType::SCALE)]
    #[Assert\Expression('value.isPositive()', message: 'This value should be positive.')]
    #[Serialize\Groups(['product_api:read', 'product_api:write'])]
    private BigDecimal $quantity;

    #[ORM\ManyToOne(targetEntity: Tax::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Serialize\Groups(['product_api:read', 'product_api:write'])]
    private ?Tax $tax = null;

    public function __construct()
    {
        $this->id = new Ulid();
        $this->quantity = BigDecimal::one();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): BigDecimal
    {
        return BigDecimal::of($this->price);
    }

    public function setPrice(BigDecimal $price): self
    {
        $this->price = (string) $price;

        return $this;
    }

    public function getQuantity(): BigDecimal
    {
        return $this->quantity;
    }

    public function setQuantity(BigDecimal $quantity): self
    {
        $this->quantity = QuantityType::normalize($quantity);

        return $this;
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
