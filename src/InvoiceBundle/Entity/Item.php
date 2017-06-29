<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Entity;

use CSBill\CoreBundle\Entity\ItemInterface;
use CSBill\CoreBundle\Traits\Entity;
use CSBill\MoneyBundle\Entity\Money as MoneyEntity;
use CSBill\TaxBundle\Entity\Tax;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation as Serialize;
use Money\Money;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CSBill\InvoiceBundle\Entity\Item.
 *
 * @ORM\Table(name="invoice_lines")
 * @ORM\Entity(repositoryClass="CSBill\InvoiceBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
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
     * @Serialize\Groups({"invoice_api"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "create_invoice_api"})
     */
    private $description;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="CSBill\MoneyBundle\Entity\Money")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "create_invoice_api"})
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="qty", type="float")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "create_invoice_api"})
     */
    private $qty;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="items")
     */
    private $invoice;

    /**
     * @ORM\ManyToOne(targetEntity="CSBill\TaxBundle\Entity\Tax", inversedBy="invoiceItems")
     * @Serialize\Groups({"invoice_api", "create_invoice_api"})
     */
    private $tax;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="CSBill\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api"})
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
     * Set the price.
     *
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
     * Get the price.
     *
     * @return Money
     */
    public function getPrice(): Money
    {
        return $this->price->getMoney();
    }

    /**
     * Set the qty.
     *
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
     * Get qty.
     *
     * @return float
     */
    public function getQty(): ?float
    {
        return $this->qty;
    }

    /**
     * Set invoice.
     *
     * @param Invoice $invoice
     *
     * @return ItemInterface
     */
    public function setInvoice(?Invoice $invoice): ItemInterface
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Get invoice.
     *
     * @return Invoice
     */
    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
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
     * Get the line item total.
     *
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
