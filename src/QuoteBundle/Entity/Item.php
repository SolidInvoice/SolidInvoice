<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Entity;

use CSBill\CoreBundle\Entity\ItemInterface;
use CSBill\CoreBundle\Traits\Entity;
use CSBill\MoneyBundle\Entity\Money as MoneyEntity;
use CSBill\TaxBundle\Entity\Tax;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Money\Money;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="quote_lines")
 * @ORM\Entity(repositoryClass="CSBill\QuoteBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @Serialize\ExclusionPolicy("all")
 */
class Item implements ItemInterface
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Expose()
     * @Serialize\Groups(groups={"js", "api"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank
     * @Serialize\Expose()
     * @Serialize\Groups(groups={"js", "api"})
     */
    private $description;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="CSBill\MoneyBundle\Entity\Money")
     * @Assert\NotBlank()
     * @Serialize\Expose()
     * @Serialize\Groups(groups={"js", "api"})
     * @Serialize\AccessType(type="public_method")
     */
    private $price;

    /**
     * @var int
     *
     * @ORM\Column(name="qty", type="float")
     * @Assert\NotBlank()
     * @Serialize\Expose()
     * @Serialize\Groups(groups={"js", "api"})
     */
    private $qty;

    /**
     * @var Quote
     *
     * @ORM\ManyToOne(targetEntity="Quote", inversedBy="items")
     */
    private $quote;

    /**
     * @ORM\ManyToOne(targetEntity="CSBill\TaxBundle\Entity\Tax", inversedBy="quoteItems")
     * @Serialize\Expose()
     * @Serialize\Groups(groups={"js", "api"})
     */
    private $tax;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="CSBill\MoneyBundle\Entity\Money")
     * @Serialize\Expose()
     * @Serialize\Groups(groups={"js", "api"})
     * @Serialize\AccessType(type="public_method")
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Item
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Money $price
     *
     * @return Item
     */
    public function setPrice(Money $price)
    {
        $this->price = new MoneyEntity($price);

        return $this;
    }

    /**
     * @return Money
     */
    public function getPrice()
    {
        return $this->price->getMoney();
    }

    /**
     * @param int $qty
     *
     * @return Item
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return int
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param Quote $quote
     *
     * @return Item
     */
    public function setQuote(Quote $quote = null)
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param Money $total
     *
     * @return Item
     */
    public function setTotal(Money $total)
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    /**
     * @return Money
     */
    public function getTotal()
    {
        return $this->total->getMoney();
    }

    /**
     * @return Tax
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param Tax $tax
     *
     * @return Item
     */
    public function setTax(Tax $tax = null)
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
    public function __toString()
    {
        return $this->description;
    }
}
