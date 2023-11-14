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

namespace SolidInvoice\QuoteBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Money\Money;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use SolidInvoice\QuoteBundle\Traits\QuoteStatusTrait;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Quote::TABLE_NAME)]
#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    uriTemplate: '/clients/{id}/quotes.{_format}',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(fromClass: Client::class, identifiers: ['id'])
    ],
    status: 200,
    normalizationContext: [
        'groups' => ['quote_api']
    ],
    denormalizationContext: [
        'groups' => ['create_quote_api']
    ],
)]
class Quote
{
    final public const TABLE_NAME = 'quotes';
    use Archivable;
    use QuoteStatusTrait {
        Archivable::isArchived insteadof QuoteStatusTrait;
    }
    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private ?UuidInterface $id = null;

    #[ORM\Column(name: 'quote_id', type: Types::STRING, length: 255)]
    private string $quoteId;

    #[ORM\Column(name: 'uuid', type: UuidType::NAME, length: 36)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private ?UuidInterface $uuid = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private ?string $status = null;

    #[ApiProperty(iris: ['https://schema.org/Organization'])]
    #[ORM\ManyToOne(targetEntity: Client::class, cascade: ['persist'], inversedBy: 'quotes')]
    #[Assert\NotBlank]
    #[Serialize\Groups(['quote_api', 'create_quote_api'])]
    private ?Client $client = null;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private MoneyEntity $total;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private MoneyEntity $baseTotal;

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['quote_api', 'client_api'])]
    private MoneyEntity $tax;

    #[ORM\Embedded(class: Discount::class)]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private Discount $discount;

    #[ORM\Column(name: 'terms', type: Types::TEXT, nullable: true)]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private ?string $terms = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private ?string $notes = null;

    #[ORM\Column(name: 'due', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\DateTime]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private ?DateTimeInterface $due = null;

    /**
     * @var Collection<int, Item>
     */
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: Item::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'You need to add at least 1 item to the Quote')]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private Collection $items;

    /**
     * @var \Collection<int,\QuoteContact>
     */
    #[ApiProperty(writableLink: true)]
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteContact::class, cascade: ['persist', 'remove'])]
    #[Assert\Count(min: 1, minMessage: 'You need to select at least 1 user to attach to the Quote')]
    #[Serialize\Groups(['quote_api', 'client_api', 'create_quote_api'])]
    private Collection $users;

    #[ORM\OneToOne(mappedBy: 'quote', targetEntity: Invoice::class)]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
        try {
            $this->setUuid(Uuid::uuid1());
        } catch (Exception) {
        }
        $this->baseTotal = new MoneyEntity();
        $this->tax = new MoneyEntity();
        $this->total = new MoneyEntity();
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

    /**
     * @return Collection<int, Contact>
     */
    public function getUsers(): Collection
    {
        return $this->users->map(static fn (QuoteContact $contact): Contact => $contact->getContact());
    }

    /**
     * @param iterable<Contact|QuoteContact> $users
     */
    public function setUsers(iterable $users): self
    {
        $contacts = [];
        foreach ($users as $user) {
            if ($user instanceof QuoteContact) {
                $contacts[] = $user;
            } elseif ($user instanceof Contact) {
                $quoteContact = new QuoteContact();
                $quoteContact->setContact($user);
                $quoteContact->setQuote($this);
                $contacts[] = $quoteContact;
            }
        }
        $this->users = new ArrayCollection($contacts);
        return $this;
    }

    public function addUser(Contact $user): self
    {
        $quoteContact = new QuoteContact();
        $quoteContact->setContact($user);
        $quoteContact->setQuote($this);
        $this->users[] = $quoteContact;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        if ($client instanceof Client && null !== $client->getCurrencyCode()) {
            $this->total->setCurrency($client->getCurrency()->getCode());
            $this->baseTotal->setCurrency($client->getCurrency()->getCode());
            $this->tax->setCurrency($client->getCurrency()->getCode());
        }
        return $this;
    }

    public function getTotal(): ?Money
    {
        return $this->total->getMoney();
    }

    public function setTotal(Money $total): self
    {
        $this->total = new MoneyEntity($total);
        return $this;
    }

    public function getBaseTotal(): ?Money
    {
        return $this->baseTotal->getMoney();
    }

    public function setBaseTotal(Money $baseTotal): self
    {
        $this->baseTotal = new MoneyEntity($baseTotal);
        return $this;
    }

    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    public function setDiscount(Discount $discount): self
    {
        $this->discount = $discount;
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

    public function addItem(ItemInterface $item): self
    {
        assert($item instanceof Item);
        $this->items[] = $item;
        $item->setQuote($this);
        return $this;
    }

    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);
        $item->setQuote();
        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): self
    {
        $this->terms = $terms;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getTax(): ?Money
    {
        return $this->tax->getMoney();
    }

    public function setTax(Money $tax): self
    {
        $this->tax = new MoneyEntity($tax);
        return $this;
    }

    #[ORM\PrePersist]
    public function updateItems(): void
    {
        foreach ($this->items as $item) {
            $item->setQuote($this);
        }
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function getQuoteId(): string
    {
        return $this->quoteId;
    }

    public function setQuoteId(string $quoteId): void
    {
        $this->quoteId = $quoteId;
    }
}
