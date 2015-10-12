<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Entity;

use CSBill\TaxBundle\Entity\Tax;
use CSBill\CoreBundle\Traits\Entity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Money;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

/**
 * @ORM\Table(name="quote_lines")
 * @ORM\Entity(repositoryClass="CSBill\QuoteBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @Serialize\ExclusionPolicy("all")
 */
class Item
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
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank
     * @Serialize\Expose()
     */
    private $description;

    /**
     * @var Money
     *
     * @ORM\Column(name="price", type="money")
     * @Assert\NotBlank()
     * @Serialize\Expose()
     */
    private $price;

    /**
     * @var int
     *
     * @ORM\Column(name="qty", type="float")
     * @Assert\NotBlank()
     * @Serialize\Expose()
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
     */
    private $tax;

    /**
     * @var Money
     *
     * @ORM\Column(name="total", type="money")
     * @Serialize\Expose()
     */
    private $total;

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
     * Set the price.
     *
     * @param Money $price
     *
     * @return Item
     */
    public function setPrice(Money $price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the price.
     *
     * @return Money
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set the qty.
     *
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
     * Get qty.
     *
     * @return int
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Set quote.
     *
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
     * Get quote.
     *
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
        $this->total = $total;

        return $this;
    }

    /**
     * Get the line item total.
     *
     * @return Money
     */
    public function getTotal()
    {
        return $this->total;
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
    public function setTax(Tax $tax)
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
        $this->total = $this->price->multiply($this->qty);
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
