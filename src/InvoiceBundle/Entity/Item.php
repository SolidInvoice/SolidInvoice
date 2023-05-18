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

namespace SolidInvoice\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\TaxBundle\Entity\Tax;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="invoice_lines")
 * @ORM\Entity(repositoryClass="SolidInvoice\InvoiceBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Item implements ItemInterface, Stringable
{
    use TimeStampable;
    use CompanyAware;

    /**
     * @ORM\Column(name="id", type="uuid_binary_ordered_time")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api"})
     */
    private ?UuidInterface $id = null;

    /**
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    private ?string $description = null;

    /**
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    private MoneyEntity $price;

    /**
     * @ORM\Column(name="qty", type="float")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    private ?float $qty = null;

    /**
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="items")
     */
    private ?Invoice $invoice = null;

    /**
     * @ORM\ManyToOne(targetEntity="RecurringInvoice", inversedBy="items")
     */
    private ?RecurringInvoice $recurringInvoice = null;

    /**
     * @ORM\ManyToOne(targetEntity="SolidInvoice\TaxBundle\Entity\Tax", inversedBy="invoiceItems")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    private ?Tax $tax = null;

    /**
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api"})
     */
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

    /**
     * @param Invoice|RecurringInvoice|null $invoice
     */
    public function setInvoice(?BaseInvoice $invoice): ItemInterface
    {
        if ($invoice instanceof RecurringInvoice) {
            $this->recurringInvoice = $invoice;
        } else {
            $this->invoice = $invoice;
        }

        return $this;
    }

    public function getInvoice(): BaseInvoice
    {
        return $this->invoice ?? $this->recurringInvoice;
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

    /**
     * @ORM\PrePersist
     */
    public function updateTotal(): void
    {
        $this->total = new MoneyEntity($this->getPrice()->multiply($this->qty));
    }

    public function __toString(): string
    {
        return (string) $this->description;
    }
}
