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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
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
use SolidInvoice\CoreBundle\Doctrine\Id\IdGenerator;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\QuoteBundle\Traits\QuoteStatusTrait;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(attributes={"normalization_context"={"groups"={"quote_api"}}, "denormalization_context"={"groups"={"create_quote_api"}}})
 * @ORM\Table(name="quotes")
 * @ORM\Entity(repositoryClass="SolidInvoice\QuoteBundle\Repository\QuoteRepository")
 * @Gedmo\Loggable
 * @ORM\HasLifecycleCallbacks()
 */
class Quote
{
    use Archivable;
    use QuoteStatusTrait {
        Archivable::isArchived insteadof QuoteStatusTrait;
    }
    use TimeStampable;
    use CompanyAware;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=IdGenerator::class)
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $id;

    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="uuid", type="uuid", length=36)
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $uuid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $status;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="quotes")
     * @Assert\NotBlank
     * @Serialize\Groups({"quote_api", "create_quote_api"})
     * @ApiProperty(iri="https://schema.org/Organization")
     */
    private $client;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $total;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $baseTotal;

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $tax;

    /**
     * @var Discount
     *
     * @ORM\Embedded(class="SolidInvoice\CoreBundle\Entity\Discount")
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $discount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $terms;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $notes;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(name="due", type="date", nullable=true)
     * @Assert\DateTime
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $due;

    /**
     * @var Collection<int, Item>
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="quote", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Quote")
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $items;

    /**
     * @var Collection<int, Contact>
     *
     * @ORM\ManyToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Contact", cascade={"persist"}, fetch="EXTRA_LAZY", inversedBy="quotes")
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Quote")
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $users;

    /**
     * @var Invoice|null
     *
     * @ORM\OneToOne(targetEntity="SolidInvoice\InvoiceBundle\Entity\Invoice", mappedBy="quote")
     */
    private $invoice;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
        try {
            $this->setUuid(Uuid::uuid1());
        } catch (Exception $e) {
        }

        $this->baseTotal = new MoneyEntity();
        $this->tax = new MoneyEntity();
        $this->total = new MoneyEntity();
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
     * Return users array.
     *
     * @return Collection<int, Contact>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @param Contact[] $users
     */
    public function setUsers(array $users): self
    {
        $this->users = new ArrayCollection($users);

        return $this;
    }

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

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getTotal(): Money
    {
        return $this->total->getMoney();
    }

    public function setTotal(Money $total): self
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    public function getBaseTotal(): Money
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

    /**
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

    /**
     * @return string
     */
    public function getTerms(): ?string
    {
        return $this->terms;
    }

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

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTax(): Money
    {
        return $this->tax->getMoney();
    }

    public function setTax(Money $tax): self
    {
        $this->tax = new MoneyEntity($tax);

        return $this;
    }

    /**
     * PrePersist listener to link the items to the quote.
     *
     * @ORM\PrePersist
     */
    public function updateItems(): void
    {
        if ((is_countable($this->items) ? count($this->items) : 0) > 0) {
            foreach ($this->items as $item) {
                $item->setQuote($this);
            }
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
}
