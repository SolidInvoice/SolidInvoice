<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Entity;

use CSBill\CoreBundle\Entity\Tax;
use CSBill\CoreBundle\Traits\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="quote_lines")
 * @ORM\Entity(repositoryClass="CSBill\QuoteBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 */
class Item
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $description
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
     * @Assert\NotBlank
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty", type="float")
     * @Assert\NotBlank
     */
    private $qty;

    /**
     * @var Quote $quote
     *
     * @ORM\ManyToOne(targetEntity="Quote", inversedBy="items")
     */
    private $quote;

    /**
     * @ORM\ManyToOne(targetEntity="CSBill\CoreBundle\Entity\Tax", inversedBy="quoteItems")
     */
    private $tax;

    /**
     * @var int
     * @ORM\Column(name="total", type="decimal", scale=2)
     */
    private $total;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set description
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
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the price
     *
     * @param float $price
     *
     * @return Item
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set the qty
     *
     * @param integer $qty
     *
     * @return Item
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * Get qty
     *
     * @return integer
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Set quote
     *
     * @param Quote $quote
     *
     * @return Item
     */
    public function setQuote(Quote $quote)
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * Get quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param float $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get the line item total
     *
     * @return float
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
     * PrePersist listener to update the line total
     *
     * @ORM\PrePersist
     */
    public function updateTotal()
    {
        $this->total = $this->qty * $this->price;
    }

    /**
     * Return the item as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->description;
    }
}
