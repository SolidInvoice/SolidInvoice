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

namespace SolidInvoice\QuoteBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\QuoteBundle\Traits\QuoteStatusTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(attributes={"normalization_context"={"groups"={"quote_api"}}, "denormalization_context"={"groups"={"create_quote_api"}}})
 * @ORM\Table(name="quotes")
 * @ORM\Entity(repositoryClass="SolidInvoice\QuoteBundle\Repository\QuoteRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @ORM\HasLifecycleCallbacks()
 */
class Quote
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable,
        Entity\Archivable,
        QuoteStatusTrait {
        Entity\Archivable::isArchived insteadof QuoteStatusTrait;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $id;

    /**
     * @var Uuid
     *
     * @ORM\Column(name="uuid", type="uuid", length=36)
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"quote_api", "client_api"})
     */
    private $status;

    /**
     * @var Client
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
     * @var string
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $terms;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due", type="date", nullable=true)
     * @Assert\DateTime
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $due;

    /**
     * @var Collection|ItemInterface[]
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="quote", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Quote")
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $items;

    /**
     * @var Collection|Contact[]
     *
     * @ORM\ManyToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Contact", cascade={"persist"}, fetch="EXTRA_LAZY", inversedBy="quotes")
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Quote")
     * @Serialize\Groups({"quote_api", "client_api", "create_quote_api"})
     */
    private $users;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->setUuid(Uuid::uuid1());

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
     * @return Quote
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
     * @return Quote
     */
    public function setUsers(array $users): self
    {
        $this->users = new ArrayCollection($users);

        return $this;
    }

    /**
     * @param Contact $user
     *
     * @return Quote
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
     * @param string $status
     *
     * @return Quote
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
     * @param Client|null $client
     *
     * @return Quote
     */
    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Money
     */
    public function getTotal(): Money
    {
        return $this->total->getMoney();
    }

    /**
     * @param Money $total
     *
     * @return Quote
     */
    public function setTotal(Money $total): self
    {
        $this->total = new MoneyEntity($total);

        return $this;
    }

    /**
     * @return Money
     */
    public function getBaseTotal(): Money
    {
        return $this->baseTotal->getMoney();
    }

    /**
     * @param Money $baseTotal
     *
     * @return Quote
     */
    public function setBaseTotal(Money $baseTotal): self
    {
        $this->baseTotal = new MoneyEntity($baseTotal);

        return $this;
    }

    /**
     * @return Discount
     */
    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    /**
     * @param Discount $discount
     *
     * @return Quote
     */
    public function setDiscount(Discount $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDue(): ?\DateTime
    {
        return $this->due;
    }

    /**
     * @param \DateTime $due
     *
     * @return Quote
     */
    public function setDue(\DateTime $due): self
    {
        $this->due = $due;

        return $this;
    }

    /**
     * @param Item $item
     *
     * @return Quote
     */
    public function addItem(Item $item): self
    {
        $this->items[] = $item;
        $item->setQuote($this);

        return $this;
    }

    /**
     * @param Item $item
     *
     * @return Quote
     */
    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);
        $item->setQuote(null);

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
     * @return string
     */
    public function getTerms(): ?string
    {
        return $this->terms;
    }

    /**
     * @param string $terms
     *
     * @return Quote
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
     * @return Quote
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
     * @return Quote
     */
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
    public function updateItems()
    {
        if (count($this->items)) {
            foreach ($this->items as $item) {
                $item->setQuote($this);
            }
        }
    }
}
