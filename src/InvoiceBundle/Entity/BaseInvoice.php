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

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass()
 */
abstract class BaseInvoice
{
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api"})
     */
    protected $status;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="invoices")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     * @ApiProperty(iri="https://schema.org/Organization")
     */
    protected $client;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api"})
     */
    protected $total;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api"})
     */
    protected $baseTotal;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api"})
     */
    protected $tax;

    /**
     * @var Discount
     *
     * @ORM\Embedded(class="SolidInvoice\CoreBundle\Entity\Discount")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    protected $discount;

    /**
     * @var string
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    protected $terms;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    protected $notes;

    /**
     * @var Collection|Contact[]
     *
     * @ORM\ManyToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Contact", cascade={"persist"}, fetch="EXTRA_LAZY", inversedBy="invoices")
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Invoice")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    protected $users;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->users = new ArrayCollection();
        $this->baseTotal = new MoneyEntity();
        $this->tax = new MoneyEntity();
        $this->total = new MoneyEntity();
    }

    /**
     * Return users array.
     *
     * @return Collection|Contact[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @param Contact[] $users
     *
     * @return Invoice
     */
    public function setUsers(array $users): self
    {
        $this->users = new ArrayCollection($users);

        return $this;
    }

    /**
     * @return Invoice
     */
    public function addUser(Contact $user): self
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @return Invoice
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get Client.
     *
     * @return Client
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * Set client.
     *
     * @param Client $client
     *
     * @return Invoice
     */
    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get total.
     */
    public function getTotal(): Money
    {
        return $this->total->getMoney();
    }

    /**
     * Set total.
     *
     * @return Invoice
     */
    public function setTotal(Money $total): self
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    /**
     * Get base total.
     */
    public function getBaseTotal(): Money
    {
        return $this->baseTotal->getMoney();
    }

    /**
     * Set base total.
     *
     * @return Invoice
     */
    public function setBaseTotal(Money $baseTotal): self
    {
        $this->baseTotal = new MoneyEntity($baseTotal);

        return $this;
    }

    /**
     * Get discount.
     */
    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    /**
     * Set discount.
     *
     * @return Invoice
     */
    public function setDiscount(Discount $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return string
     */
    public function getTerms(): ?string
    {
        return $this->terms;
    }

    /**
     * @param string $terms
     *
     * @return Invoice
     */
    public function setTerms(?string $terms): self
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     *
     * @return Invoice
     */
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTax(): Money
    {
        return $this->tax->getMoney();
    }

    /**
     * @return Invoice
     */
    public function setTax(Money $tax): self
    {
        $this->tax = new MoneyEntity($tax);

        return $this;
    }

    /**
     * PrePersist listener to update the invoice total.
     *
     * @ORM\PrePersist
     */
    public function updateItems()
    {
        if (count($this->items)) {
            foreach ($this->items as $item) {
                $item->setInvoice($this);
            }
        }
    }

    public function __clone()
    {
        if (null !== $this->items) {
            $items = $this->items;
            $this->items = new ArrayCollection();
            foreach ($items as $item) {
                $this->items->add(clone $item);
            }
        }

        $this->status = null;
    }
}
