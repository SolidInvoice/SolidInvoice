<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Entity;

use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\TaxBundle\Entity\Tax;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation as Serialize;
use Money\Money;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="quote_lines")
 * @ORM\Entity(repositoryClass="SolidInvoice\QuoteBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable()
 */
class Item implements ItemInterface
{
    use Entity\TimeStampable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $description;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Assert\NotBlank()
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="qty", type="float")
     * @Assert\NotBlank()
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $qty;

    /**
     * @var Quote
     *
     * @ORM\ManyToOne(targetEntity="Quote", inversedBy="items")
     */
    private $quote;

    /**
     * @ORM\ManyToOne(targetEntity="SolidInvoice\TaxBundle\Entity\Tax", inversedBy="quoteItems")
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $tax;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $total;

    public function __construct()
    {
        $this->total = new MoneyEntity();
        $this->price = new MoneyEntity();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return ItemInterface
     */
    public function setDescription(string $description): ItemInterface
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param Money $price
     *
     * @return ItemInterface
     */
    public function setPrice(Money $price): ItemInterface
    {
        $this->price = new MoneyEntity($price);

        return $this;
    }

    /**
     * @return Money
     */
    public function getPrice(): Money
    {
        return $this->price->getMoney();
    }

    /**
     * @param float $qty
     *
     * @return ItemInterface
     */
    public function setQty(float $qty): ItemInterface
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return float
     */
    public function getQty(): ?float
    {
        return $this->qty;
    }

    /**
     * @param Quote $quote
     *
     * @return ItemInterface
     */
    public function setQuote(Quote $quote = null): ItemInterface
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * @return Quote
     */
    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    /**
     * @param Money $total
     *
     * @return ItemInterface
     */
    public function setTotal(Money $total): ItemInterface
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    /**
     * @return Money
     */
    public function getTotal(): Money
    {
        return $this->total->getMoney();
    }

    /**
     * @return Tax
     */
    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    /**
     * @param Tax $tax
     *
     * @return ItemInterface
     */
    public function setTax(?Tax $tax): ItemInterface
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * PrePersist listener to update the line total.
     *
     * @ORM\PrePersist
     */
    public function updateTotal()
    {
        $this->total = new MoneyEntity($this->getPrice()->multiply($this->qty));
    }

    /**
     * Return the item as a string.
     *
     * @return string
     */
    public function __toString(): ?string
    {
        return $this->getDescription();
    }
}
