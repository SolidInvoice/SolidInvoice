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

use Doctrine\DBAL\Types\Types;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\InvoiceBundle\Entity\Item as InvoiceItem;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Item as QuoteItem;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Tax::TABLE_NAME)]
#[ORM\Entity(repositoryClass: TaxRepository::class)]
#[UniqueEntity('name')]
class Tax implements Stringable
{
    final public const TABLE_NAME = 'tax_rates';

    use TimeStampable;
    use CompanyAware;

    final public const TYPE_INCLUSIVE = 'Inclusive';

    final public const TYPE_EXCLUSIVE = 'Exclusive';

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 32)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(name: 'rate', type: Types::FLOAT, precision: 4)]
    #[Assert\Type('float')]
    #[Assert\NotBlank]
    private ?float $rate = null;

    #[ORM\Column(name: 'tax_type', type: Types::STRING, length: 32)]
    #[Assert\NotBlank]
    private ?string $type = null;

    /**
     * @var Collection<int, Item>
     */
    #[ORM\OneToMany(mappedBy: 'tax', targetEntity: InvoiceItem::class)]
    private Collection $invoiceItems;

    /**
     * @var Collection<int, QuoteItem>
     */
    #[ORM\OneToMany(mappedBy: 'tax', targetEntity: QuoteItem::class)]
    private Collection $quoteItems;

    public function __construct()
    {
        $this->invoiceItems = new ArrayCollection();
        $this->quoteItems = new ArrayCollection();
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

    public function getId(): UuidInterface
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
     * @return Collection<int, Item>
     */
    public function getInvoiceItems(): Collection
    {
        return $this->invoiceItems;
    }

    /**
     * @param Item[] $invoiceItems
     */
    public function setInvoiceItems(array $invoiceItems): self
    {
        $this->invoiceItems = new ArrayCollection($invoiceItems);

        return $this;
    }

    /**
     * @return Collection<int, QuoteItem>
     */
    public function getQuoteItems(): Collection
    {
        return $this->quoteItems;
    }

    /**
     * @param QuoteItem[] $quoteItems
     */
    public function setQuoteItems(array $quoteItems): self
    {
        $this->quoteItems = new ArrayCollection($quoteItems);

        return $this;
    }

    public function __toString(): string
    {
        $type = self::TYPE_INCLUSIVE === $this->type ? 'inc' : 'exl';

        return "{$this->rate}% {$this->name} ({$type})";
    }
}
