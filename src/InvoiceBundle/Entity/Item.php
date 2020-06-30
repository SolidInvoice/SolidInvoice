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

namespace SolidInvoice\InvoiceBundle\Entity;

use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Money;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="invoice_lines")
 * @ORM\Entity(repositoryClass="SolidInvoice\InvoiceBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable
 */
class Item implements ItemInterface
{
    use TimeStampable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $description;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="qty", type="float")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $qty;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="items")
     */
    private $invoice;

    /**
     * @ORM\ManyToOne(targetEntity="SolidInvoice\TaxBundle\Entity\Tax", inversedBy="invoiceItems")
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $tax;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "client_api"})
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
     */
    public function setPrice(Money $price): ItemInterface
    {
        $this->price = new MoneyEntity($price);

        return $this;
    }

    /**
     * Get the price.
     */
    public function getPrice(): Money
    {
        return $this->price->getMoney();
    }

    /**
     * Set the qty.
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

    public function setTotal(Money $total): ItemInterface
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    /**
     * Get the line item total.
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
     */
    public function __toString(): string
    {
        return (string) $this->getDescription();
    }
}
