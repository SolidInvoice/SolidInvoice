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
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ApiBundle\Serializer\Normalizer\BigIntegerNormalizer;
use SolidInvoice\CoreBundle\Billing\LineName;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\ProductBundle\Security\ProductsAccessVoter;
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
    security: "is_granted('" . ProductsAccessVoter::ACCESS . "')",
)]
#[ORM\Table(name: Product::TABLE_NAME)]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product implements Stringable
{
    final public const string TABLE_NAME = 'products';

    private const int PRICE_PRECISION = 19;

    private const int PRICE_SCALE = 6;

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
     * A major-unit decimal amount with no currency, stored as an exact `DECIMAL(19, 6)` (the
     * {@see QuantityType} is the codebase's exact-decimal type, and gives the serializer a
     * {@see BigDecimal} to work with where a plain `DECIMAL` column would have it see a string).
     * Exposed over the API as a JSON number, exactly as stored, never scaled to a minor unit.
     */
    #[ORM\Column(name: 'price', type: QuantityType::NAME, precision: self::PRICE_PRECISION, scale: self::PRICE_SCALE)]
    #[Assert\Expression('value.isGreaterThanOrEqualTo(0)', message: 'product.price.negative')]
    #[Assert\Expression('value.getScale() <= ' . self::PRICE_SCALE, message: 'product.price.too_many_decimals')]
    #[Assert\Expression('value.abs().isLessThan(10 ** ' . (self::PRICE_PRECISION - self::PRICE_SCALE) . ')', message: 'product.price.too_large')]
    #[Serialize\Groups(['product_api:read', 'product_api:write', 'searchable'])]
    #[Serialize\Context(
        normalizationContext: [BigIntegerNormalizer::MONETARY => false],
        denormalizationContext: [BigIntegerNormalizer::MONETARY => false],
    )]
    #[ApiProperty(
        description: 'The unit price as a plain decimal number in major units (for example 4500.5), with no currency: it takes the currency of the document the product is added to. At most ' . (self::PRICE_PRECISION - self::PRICE_SCALE) . ' digits before and ' . self::PRICE_SCALE . ' after the decimal point.',
        openapiContext: ['type' => 'number'],
        jsonSchemaContext: ['type' => 'number'],
    )]
    private BigDecimal $price;

    #[ORM\Column(name: 'quantity', type: QuantityType::NAME, precision: QuantityType::PRECISION, scale: QuantityType::SCALE)]
    #[Assert\Expression('value.isPositive()', message: 'product.quantity.not_positive')]
    #[Assert\Expression('value.abs().isLessThan(10 ** ' . (QuantityType::PRECISION - QuantityType::SCALE) . ')', message: 'product.quantity.too_large')]
    #[Serialize\Groups(['product_api:read', 'product_api:write'])]
    #[Serialize\Context(
        normalizationContext: [BigIntegerNormalizer::MONETARY => false],
        denormalizationContext: [BigIntegerNormalizer::MONETARY => false],
    )]
    #[ApiProperty(
        description: 'The default quantity, as a plain decimal number. Rounded to 6 decimal places, with at most 14 digits before the decimal point.',
        openapiContext: ['type' => 'number'],
        jsonSchemaContext: ['type' => 'number'],
    )]
    private BigDecimal $quantity;

    #[ORM\ManyToOne(targetEntity: Tax::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Serialize\Groups(['product_api:read', 'product_api:write'])]
    private ?Tax $tax = null;

    public function __construct()
    {
        $this->id = new Ulid();
        $this->price = BigDecimal::zero();
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
        return $this->price;
    }

    /**
     * @throws MathException
     */
    public function setPrice(BigNumber $price): self
    {
        $this->price = $price->toBigDecimal();

        return $this;
    }

    public function getQuantity(): BigDecimal
    {
        return $this->quantity;
    }

    /**
     * @throws MathException
     */
    public function setQuantity(BigNumber $quantity): self
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
