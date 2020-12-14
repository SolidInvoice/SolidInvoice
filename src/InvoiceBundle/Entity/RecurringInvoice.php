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
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"recurring_invoice_api", "client_api"})
     */
    private $id;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="recurringInvoices")
     * @Assert\NotBlank
     * @Serialize\Groups({"invoice_api", "recurring_invoice_api", "client_api", "create_invoice_api", "create_recurring_invoice_api"})
     * @ApiProperty(iri="https://schema.org/Organization")
     */
    protected $client;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency", type="string", nullable=true)
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    private $frequency;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_start", type="date")
     * @Assert\NotBlank(groups={"Recurring"})
     * @Assert\Date(groups={"Recurring"})
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    private $dateStart;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_end", type="date", nullable=true)
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    private $dateEnd;

    /**
     * @var Collection|ItemInterface[]
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="recurringInvoice", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Invoice")
     * @Serialize\Groups({"recurring_invoice_api", "client_api", "create_recurring_invoice_api"})
     */
    protected $items;

    /**
     * @var Collection|Contact[]
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

    /**
     * Set client.
     *
     * @param Client|null $client
     *
     * @return RecurringInvoice
     */
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

    /**
     * @return RecurringInvoice
     */
    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateStart(): ?DateTime
    {
        return $this->dateStart;
    }

    /**
     * @param DateTime $dateStart
     *
     * @return RecurringInvoice
     */
    public function setDateStart(DateTime $dateStart = null): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateEnd(): ?DateTime
    {
        return $this->dateEnd;
    }

    /**
     * @param DateTime $dateEnd
     *
     * @return RecurringInvoice
     */
    public function setDateEnd(DateTime $dateEnd = null): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Add item.
     *
     * @return RecurringInvoice
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
     * @return RecurringInvoice
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
     *
     * @return RecurringInvoice
     */
    public function setUsers(array $users): self
    {
        $this->users = new ArrayCollection($users);

        return $this;
    }

    /**
     * @return RecurringInvoice
     */
    public function addUser(Contact $user): self
    {
        $this->users[] = $user;

        return $this;
    }
}
