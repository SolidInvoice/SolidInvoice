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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Doctrine\Id\IdGenerator;
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
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=IdGenerator::class)
     * @Serialize\Groups({"invoice_api", "client_api"})
     */
    private $id;

    /**
     * @var UuidInterface
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
     * @var Collection<int, Payment>
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
     * @var Collection<int, Item>
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="invoice", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Invoice")
     * @Serialize\Groups({"invoice_api", "client_api", "create_invoice_api"})
     */
    protected $items;

    /**
     * @var Collection<int, InvoiceContact>
     *
     * @ORM\OneToMany(targetEntity=InvoiceContact::class, cascade={"persist", "remove"}, fetch="EXTRA_LAZY", mappedBy="invoice")
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Invoice")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     * @ApiProperty(writableLink=true)
     */
    protected Collection $users;

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

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

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

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getBalance(): Money
    {
        return $this->balance->getMoney();
    }

    public function setBalance(Money $balance): self
    {
        $this->balance = new MoneyEntity($balance);

        return $this;
    }

    /**
     * Get due.
     *
     * @return DateTimeInterface
     */
    public function getDue(): ?DateTimeInterface
    {
        return $this->due;
    }

    public function setDue(DateTimeInterface $due): self
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Get paidDate.
     *
     * @return DateTimeInterface
     */
    public function getPaidDate(): ?DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(DateTime $paidDate): self
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    public function addItem(ItemInterface $item): self
    {
        assert($item instanceof Item);

        $this->items[] = $item;
        $item->setInvoice($this);

        return $this;
    }

    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);
        $item->setInvoice(null);

        return $this;
    }

    /**
     * @return Collection<int, Item>
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
        foreach ($this->items as $item) {
            $item->setInvoice($this);
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
     */
    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Get payments.
     *
     * @return Collection<int, Payment>
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
     * @return Collection<int, Contact>
     */
    public function getUsers(): Collection
    {
        return $this->users->map(static fn (InvoiceContact $invoiceContact) => $invoiceContact->getContact());
    }

    /**
     * @param (Contact|InvoiceContact)[] $users
     */
    public function setUsers(array $users): self
    {
        $contacts = [];

        foreach ($users as $user) {
            if ($user instanceof InvoiceContact) {
                $contacts[] = $user;
            } elseif ($user instanceof Contact) {
                $invoiceContact = new InvoiceContact();
                $invoiceContact->setContact($user);
                $invoiceContact->setInvoice($this);

                $contacts[] = $invoiceContact;
            }
        }

        $this->users = new ArrayCollection($contacts);

        return $this;
    }

    public function addUser(Contact $user): self
    {
        $invoiceContact = new InvoiceContact();
        $invoiceContact->setContact($user);
        $invoiceContact->setInvoice($this);

        $this->users[] = $invoiceContact;

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
