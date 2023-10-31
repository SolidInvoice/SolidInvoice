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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\QuoteBundle\Repository\ItemRepository;
use SolidInvoice\TaxBundle\Entity\Tax;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Item::TABLE_NAME)]
#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Item implements ItemInterface, Stringable
{
    final public const TABLE_NAME = 'quote_lines';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private ?UuidInterface $id = null;

    #[ORM\Column(name: 'description', type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private ?string $description = null;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Assert\NotBlank]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private MoneyEntity $price;

    #[ORM\Column(name: 'qty', type: Types::FLOAT)]
    #[Assert\NotBlank]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private ?float $qty = null;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'items')]
    private ?Quote $quote = null;

    #[ORM\ManyToOne(targetEntity: Tax::class, inversedBy: 'quoteItems')]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private ?Tax $tax = null;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private MoneyEntity $total;

    public function __construct()
    {
        $this->total = new MoneyEntity();
        $this->price = new MoneyEntity();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setDescription(string $description): ItemInterface
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPrice(Money $price): ItemInterface
    {
        $this->price = new MoneyEntity($price);

        return $this;
    }

    public function getPrice(): ?Money
    {
        return $this->price->getMoney();
    }

    public function setQty(float $qty): ItemInterface
    {
        $this->qty = $qty;

        return $this;
    }

    public function getQty(): ?float
    {
        return $this->qty;
    }

    public function setQuote(Quote $quote = null): ItemInterface
    {
        $this->quote = $quote;

        return $this;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setTotal(Money $total): ItemInterface
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    public function getTotal(): Money
    {
        return $this->total->getMoney();
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): ItemInterface
    {
        $this->tax = $tax;

        return $this;
    }

    #[ORM\PrePersist]
    public function updateTotal(): void
    {
        $this->total = new MoneyEntity($this->getPrice()->multiply($this->qty));
    }

    public function __toString(): string
    {
        return (string) $this->description;
    }
}
