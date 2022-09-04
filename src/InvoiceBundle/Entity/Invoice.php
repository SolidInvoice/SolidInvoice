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

use DateTimeInterface;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Traits\InvoiceStatusTrait;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(attributes={"normalization_context"={"groups"={"invoice_api"}}, "denormalization_context"={"groups"={"create_invoice_api"}}})
 * @ORM\Table(name="invoices", indexes={@ORM\Index(columns={"quote_id"})})
 * @ORM\Entity(repositoryClass="SolidInvoice\InvoiceBundle\Repository\InvoiceRepository")
 * @Gedmo\Loggable()
 */
class Invoice extends BaseInvoice
{
    use Archivable;
    use InvoiceStatusTrait {
        Archivable::isArchived insteadof InvoiceStatusTrait;
    }
    use TimeStampable;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
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
     * @var Client|null
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
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $balance;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="due", type="date", nullable=true)
     * @Assert\DateTime
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    private $due;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="paid_date", type="datetime", nullable=true)
     * @Assert\DateTime
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $paidDate;

    /**
     * @var Collection<Payment>
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\PaymentBundle\Entity\Payment", mappedBy="invoice", cascade={"persist"}, orphanRemoval=true)
     * @Serialize\Groups({"js"})
     */
    private $payments;

    /**
     * @var Quote|null
     *
     * @ORM\OneToOne(targetEntity="SolidInvoice\QuoteBundle\Entity\Quote", inversedBy="invoice")
     */
    private $quote;

    /**
     * @var Collection<Item>
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="invoice", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Invoice")
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    protected $items;

    /**
     * @var Collection<Contact>
     *
     * @ORM\ManyToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Contact", cascade={"persist"}, fetch="EXTRA_LAZY", inversedBy="invoices")
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Invoice")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    protected $users;

    public function __construct()
    {
        parent::__construct();

        $this->payments = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();

        try {
            $this->setUuid(Uuid::uuid1());
        } catch (Exception $e) {
        }
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

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    /**
     * @return Invoice
     */
    public function setUuid(UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

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
     * @return Invoice
     */
    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getBalance(): Money
    {
        return $this->balance->getMoney();
    }

    /**
     * @return Invoice
     */
    public function setBalance(Money $balance): self
    {
        $this->balance = new MoneyEntity($balance);

        return $this;
    }

    /**
     * Get due.
     *
     * @return DateTime
     */
    public function getDue(): ?DateTime
    {
        return $this->due;
    }

    /**
     * Set due.
     *
     * @return Invoice
     */
    public function setDue(DateTime $due): self
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Get paidDate.
     *
     * @return DateTime
     */
    public function getPaidDate(): ?DateTime
    {
        return $this->paidDate;
    }

    /**
     * Set paidDate.
     *
     * @return Invoice
     */
    public function setPaidDate(DateTime $paidDate): self
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    /**
     * @return Invoice
     */
    public function addItem(ItemInterface $item): self
    {
        assert($item instanceof Item);

        $this->items[] = $item;
        $item->setInvoice($this);

        return $this;
    }

    /**
     * @return Invoice
     */
    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);
        $item->setInvoice(null);

        return $this;
    }

    /**
     * @return Collection|ItemInterface[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * @ORM\PrePersist
     */
    public function updateItems(): void
    {
        if ((is_countable($this->items) ? count($this->items) : 0) > 0) {
            foreach ($this->items as $item) {
                $item->setInvoice($this);
            }
        }
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;
        $payment->setInvoice($this);

        return $this;
    }

    /**
     * Removes a payment.
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
     * @return Payment[]|Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(Quote $quote): self
    {
        $this->quote = $quote;
        $quote->setInvoice($this);

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
     * @return Invoice
     */
    public function addUser(Contact $user): self
    {
        $this->users[] = $user;

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

        try {
            $this->setUuid(Uuid::uuid1());
        } catch (Exception $e) {
        }
    }
}
