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
use ApiPlatform\Core\Annotation\ApiResource;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity;
use SolidInvoice\InvoiceBundle\Traits\InvoiceStatusTrait;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\PaymentBundle\Entity\Payment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation as Serialize;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(attributes={"normalization_context"={"groups"={"invoice_api"}}, "denormalization_context"={"groups"={"create_invoice_api"}}})
 * @ORM\Table(name="invoices")
 * @ORM\Entity(repositoryClass="SolidInvoice\InvoiceBundle\Repository\InvoiceRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @ORM\HasLifecycleCallbacks()
 */
class Invoice
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable,
        Entity\Archivable,
        InvoiceStatusTrait {
            Entity\Archivable::isArchived insteadof InvoiceStatusTrait;
        }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $id;

    /**
     * @var Uuid
     *
     * @ORM\Column(name="uuid", type="uuid", length=36)
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $status;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="invoices")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "create_invoice_api"})
     * @ApiProperty(iri="https://schema.org/Organization")
     */
    private $client;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $total;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $baseTotal;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $balance;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $tax;

    /**
     * @var Discount
     *
     * @ORM\Embedded(class="SolidInvoice\CoreBundle\Entity\Discount")
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $discount;

    /**
     * @var string
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $terms;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due", type="date", nullable=true)
     * @Assert\DateTime
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $due;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="paid_date", type="datetime", nullable=true)
     * @Assert\DateTime
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $paidDate;

    /**
     * @var Collection|ItemInterface[]
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="invoice", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Invoice")
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $items;

    /**
     * @var Collection|Payment[]
     *
     * @ORM\OneToMany(
     *     targetEntity="SolidInvoice\PaymentBundle\Entity\Payment",
     *     mappedBy="invoice",
     *     cascade={"persist"}
     * )
     * @Serialize\Groups({"js"})
     */
    private $payments;

    /**
     * @var Collection|Contact[]
     *
     * @ORM\ManyToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Contact", cascade={"persist"}, fetch="EXTRA_LAZY", inversedBy="invoices")
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Invoice")
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $users;

    /**
     * @var RecurringInvoice
     *
     * @ORM\OneToOne(targetEntity="SolidInvoice\InvoiceBundle\Entity\RecurringInvoice", mappedBy="invoice", cascade={"ALL"})
     * @Assert\Valid()
     */
    private $recurringInfo;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_recurring", type="boolean")
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $recurring;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->items = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->setUuid(Uuid::uuid1());
        $this->recurring = false;

        $this->baseTotal = new MoneyEntity();
        $this->tax = new MoneyEntity();
        $this->total = new MoneyEntity();
    }

    /**
     * @return Uuid
     */
    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface $uuid
     *
     * @return Invoice
     */
    public function setUuid(UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
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
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @param string $status
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
     *
     * @return Money
     */
    public function getTotal(): Money
    {
        return $this->total->getMoney();
    }

    /**
     * Set total.
     *
     * @param Money $total
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
     *
     * @return Money
     */
    public function getBaseTotal(): Money
    {
        return $this->baseTotal->getMoney();
    }

    /**
     * Set base total.
     *
     * @param Money $baseTotal
     *
     * @return Invoice
     */
    public function setBaseTotal(Money $baseTotal): self
    {
        $this->baseTotal = new MoneyEntity($baseTotal);

        return $this;
    }

    /**
     * @return Money
     */
    public function getBalance(): Money
    {
        return $this->balance->getMoney();
    }

    /**
     * @param Money $balance
     *
     * @return Invoice
     */
    public function setBalance(Money $balance): self
    {
        $this->balance = new MoneyEntity($balance);

        return $this;
    }

    /**
     * Get discount.
     *
     * @return Discount
     */
    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    /**
     * Set discount.
     *
     * @param Discount $discount
     *
     * @return Invoice
     */
    public function setDiscount(Discount $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get due.
     *
     * @return \DateTime
     */
    public function getDue(): ?\DateTime
    {
        return $this->due;
    }

    /**
     * Set due.
     *
     * @param \DateTime $due
     *
     * @return Invoice
     */
    public function setDue(\DateTime $due): self
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Get paidDate.
     *
     * @return \DateTime
     */
    public function getPaidDate(): ?\DateTime
    {
        return $this->paidDate;
    }

    /**
     * Set paidDate.
     *
     * @param \DateTime $paidDate
     *
     * @return Invoice
     */
    public function setPaidDate(\DateTime $paidDate): self
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    /**
     * Add item.
     *
     * @param Item $item
     *
     * @return Invoice
     */
    public function addItem(Item $item): self
    {
        $this->items[] = $item;
        $item->setInvoice($this);

        return $this;
    }

    /**
     * Removes an item.
     *
     * @param Item $item
     *
     * @return Invoice
     */
    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);
        $item->setInvoice(null);

        return $this;
    }

    /**
     * Get items.
     *
     * @return Collection|ItemInterface[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Add payment.
     *
     * @param Payment $payment
     *
     * @return Invoice
     */
    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;
        $payment->setInvoice($this);

        return $this;
    }

    /**
     * Removes a payment.
     *
     * @param Payment $payment
     *
     * @return Invoice
     */
    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Get payments.
     *
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
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

    /**
     * @return Money
     */
    public function getTax(): Money
    {
        return $this->tax->getMoney();
    }

    /**
     * @param Money $tax
     *
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

    /**
     * @return bool
     */
    public function isRecurring(): bool
    {
        return $this->recurringInfo instanceof RecurringInvoice;
    }

    /**
     * @param bool $recurring
     *
     * @return Invoice
     */
    public function setRecurring(bool $recurring): self
    {
        $this->recurring = $recurring;

        return $this;
    }

    /**
     * @return RecurringInvoice
     */
    public function getRecurringInfo(): ?RecurringInvoice
    {
        return $this->recurringInfo;
    }

    /**
     * @param RecurringInvoice $recurringInfo
     *
     * @return Invoice
     */
    public function setRecurringInfo(RecurringInvoice $recurringInfo = null): self
    {
        if (null === $recurringInfo) {
            return $this;
        }

        if (null !== $recurringInfo->getFrequency() && null !== $recurringInfo->getDateStart()) {
            $this->recurringInfo = $recurringInfo;
            $recurringInfo->setInvoice($this);
        }

        return $this;
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

        $this->setUuid(Uuid::uuid1());
        $this->recurring = false;
        $this->status = null;
    }
}
