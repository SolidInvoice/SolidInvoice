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
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Doctrine\Id\IdGenerator;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(attributes={"normalization_context"={"groups"={"recurring_invoice_api"}}, "denormalization_context"={"groups"={"create_recurring_invoice_api"}}})
 * @ORM\Entity(repositoryClass="SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository")
 * @ORM\Table(name="recurring_invoices")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\Loggable()
 */
class RecurringInvoice extends BaseInvoice
{
    use Archivable;
    use TimeStampable;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=IdGenerator::class)
     * @Serialize\Groups({"recurring_invoice_api", "client_api"})
     */
    private $id;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="recurringInvoices")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     * @ApiProperty(iri="https://schema.org/Organization")
     */
    protected $client;

    /**
     * @var string|null
     *
     * @ORM\Column(name="frequency", type="string", nullable=true)
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    private $frequency;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_start", type="date_immutable")
     * @Assert\NotBlank(groups={"Recurring"})
     * @Assert\Date(groups={"Recurring"})
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    private $dateStart;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="date_end", type="date_immutable", nullable=true)
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    private $dateEnd;

    /**
     * @var Collection<Item>
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="recurringInvoice", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Invoice")
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    protected $items;

    /**
     * @var Collection<Contact>
     *
     * @ORM\ManyToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Contact", cascade={"persist"}, fetch="EXTRA_LAZY", inversedBy="recurringInvoices")
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Invoice")
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     */
    protected $users;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
        parent::__construct();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return string
     */
    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getDateStart(): ?DateTimeInterface
    {
        return $this->dateStart;
    }

    /**
     * @param DateTimeInterface $dateStart
     */
    public function setDateStart(DateTimeInterface $dateStart = null): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getDateEnd(): ?DateTimeInterface
    {
        return $this->dateEnd;
    }

    /**
     * @param DateTimeInterface $dateEnd
     */
    public function setDateEnd(DateTimeInterface $dateEnd = null): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Add item.
     */
    public function addItem(Item $item): self
    {
        $this->items[] = $item;
        $item->setInvoice($this);

        return $this;
    }

    /**
     * Removes an item.
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
}
