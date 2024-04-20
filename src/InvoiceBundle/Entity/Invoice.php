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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Traits\InvoiceStatusTrait;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Invoice::TABLE_NAME)]
#[ORM\Index(columns: ['quote_id'])]
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ApiResource(
    normalizationContext: [
        'groups' => ['invoice_api']
    ],
    denormalizationContext: [
        'groups' => ['create_invoice_api']
    ],
)]
class Invoice extends BaseInvoice
{
    final public const TABLE_NAME = 'invoices';
    use Archivable;
    use InvoiceStatusTrait {
        Archivable::isArchived insteadof InvoiceStatusTrait;
    }
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[Serialize\Groups(['invoice_api', 'client_api'])]
    private ?UuidInterface $id = null;

    #[ORM\Column(name: 'invoice_id', type: Types::STRING, length: 255)]
    private string $invoiceId;

    #[ORM\Column(name: 'uuid', type: UuidType::NAME, length: 36)]
    #[Serialize\Groups(['invoice_api', 'client_api'])]
    private ?UuidInterface $uuid = null;

    #[ApiProperty(iris: ['https://schema.org/Organization'])]
    #[ORM\ManyToOne(targetEntity: Client::class, cascade: ['persist'], inversedBy: 'invoices')]
    #[Assert\NotBlank]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api'])]
    protected ?Client $client = null;

    #[ORM\Column(name: 'balance_amount', type: BigIntegerType::NAME)]
    #[Serialize\Groups(['invoice_api', 'client_api'])]
    private BigInteger $balance;

    #[ORM\Column(name: 'due', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\DateTime]
    #[Serialize\Groups(['invoice_api', 'client_api', 'create_invoice_api'])]
    private ?DateTimeInterface $due = null;

    #[ORM\Column(name: 'paid_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\DateTime]
    #[Serialize\Groups(['invoice_api', 'client_api'])]
    private ?DateTime $paidDate = null;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: Payment::class, cascade: ['persist'], orphanRemoval: true)]
    #[Serialize\Groups(['js'])]
    private Collection $payments;

    #[ORM\OneToOne(inversedBy: 'invoice', targetEntity: Quote::class)]
    private ?Quote $quote = null;

    /**
     * @var Collection<int, Item>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: Item::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'You need to add at least 1 item to the Invoice')]
    #[Serialize\Groups(['invoice_api', 'client_api', 'create_invoice_api'])]
    protected Collection $items;

    /**
     * @var Collection<int,InvoiceContact>
     */
    #[ApiProperty(writableLink: true)]
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceContact::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[Assert\Count(min: 1, minMessage: 'You need to select at least 1 user to attach to the Invoice')]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api'])]
    protected Collection $users;

    public function __construct()
    {
        parent::__construct();

        $this->payments = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->balance = BigInteger::zero();

        try {
            $this->setUuid(Uuid::uuid1());
        } catch (Exception) {
        }
    }

    public function getId(): ?UuidInterface
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

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getBalance(): BigInteger
    {
        return $this->balance;
    }

    /**
     * @throws MathException
     */
    public function setBalance(BigInteger|float|int|string $balance): self
    {
        $this->balance = BigInteger::of($balance);

        return $this;
    }

    public function getDue(): ?DateTimeInterface
    {
        return $this->due;
    }

    public function setDue(DateTimeInterface $due): self
    {
        $this->due = $due;
        return $this;
    }

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
        if (isset($this->company)) {
            $item->setCompany($this->getCompany());
        }
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

    #[ORM\PrePersist]
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

    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);
        return $this;
    }

    /**
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
        $items = $this->items;
        $this->items = new ArrayCollection();
        foreach ($items as $item) {
            $this->items->add(clone $item);
        }
        try {
            $this->setUuid(Uuid::uuid1());
        } catch (Exception) {
        }
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }
}
