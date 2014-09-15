<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use CSBill\ClientBundle\Entity\Client;
use APY\DataGridBundle\Grid\Mapping as Grid;

/**
 * CSBill\ClientBundle\Entity\Quote
 *
 * @ORM\Table(name="quotes")
 * @ORM\Entity(repositoryClass="CSBill\QuoteBundle\Repository\QuoteRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable(fieldName="deleted")
 * @ORM\HasLifecycleCallbacks()
 */
class Quote
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Uuid $uuid
     *
     * @ORM\Column(name="uuid", type="uuid", length=36)
     */
    private $uuid;

    /**
     * @var Status $status
     *
     * @ORM\ManyToOne(targetEntity="Status", inversedBy="quotes")
     * @Grid\Column(name="status", field="status.name", title="status", filter="select", selectFrom="source")
     * @Grid\Column(field="status.label", visible=false)
     */
    private $status;

    /**
     * @var Client $client
     *
     * @ORM\ManyToOne(targetEntity="CSBill\ClientBundle\Entity\Client", inversedBy="quotes")
     * @Assert\NotBlank
     * @Grid\Column(name="clients", field="client.name", title="client", filter="select", selectFrom="source")
     */
    private $client;

    /**
     * @var float
     *
     * @ORM\Column(name="total", type="float")
     * @Grid\Column(type="number", style="currency")
     */
    private $total;

    /**
     * @var float
     *
     * @ORM\Column(name="base_total", type="float")
     */
    private $baseTotal;

    /**
     * @var float
     *
     * @ORM\Column(name="tax", type="float", nullable=true)
     * @Grid\Column(type="number", style="currency")
     */
    private $tax;

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="float", nullable=true)
     */
    private $discount;


    /**
     * @var string
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     * @Grid\Column(visible=false)
     */
    private $terms;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @Grid\Column(visible=false)
     */
    private $notes;

    /**
     * @var \DateTime $due
     *
     * @ORM\Column(name="due", type="date", nullable=true)
     * @Assert\DateTime
     */
    private $due;

    /**
     * @var \DateTIme $created
     *
     * @ORM\Column(name="created", type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @Assert\DateTime
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Assert\DateTime
     */
    private $updated;

    /**
     * @var \DateTime $deleted
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     * @Assert\DateTime
     */
    private $deleted;

    /**
     * @var ArrayCollection $items
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="quote", cascade={"persist"})
     * @Orm\OrderBy({"description" = "ASC"})
     * @Assert\Valid
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Quote")
     */
    private $items;

    /**
     * @ORM\Column(name="users", type="array", nullable=false)
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Quote")
     *
     * @var array
     */
    private $users;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->items = new ArrayCollection;
        $this->users = new ArrayCollection;
        $this->setUuid(Uuid::uuid1());
    }

    /**
     * @return Uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param  Uuid  $uuid
     * @return $this
     */
    public function setUuid(Uuid $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Return users array
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param  array $users
     * @return $this
     */
    public function setUsers(array $users = array())
    {
        $this->users = new ArrayCollection($users);

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set status
     *
     * @param  Status $status
     * @return Quote
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set client
     *
     * @param  Client|null $client
     * @return Quote
     */
    public function setClient(Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get Client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set total
     *
     * @param  float $total
     * @return Quote
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set base total
     *
     * @param  float $baseTotal
     * @return Quote
     */
    public function setBaseTotal($baseTotal)
    {
        $this->baseTotal = $baseTotal;

        return $this;
    }

    /**
     * Get base total
     *
     * @return float
     */
    public function getBaseTotal()
    {
        return $this->baseTotal;
    }

    /**
     * Set discount
     *
     * @param  float $discount
     * @return Quote
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set due
     *
     * @param  \DateTime $due
     * @return Quote
     */
    public function setDue(\DateTime $due)
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Get due
     *
     * @return \DateTime
     */
    public function getDue()
    {
        return $this->due;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return Quote
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param  \DateTime $updated
     * @return Quote
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set deleted
     *
     * @param  \DateTime $deleted
     * @return Quote
     */
    public function setDeleted(\DateTime $deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getDeleted()
    {
        return $this->created;
    }

    /**
     * Add item
     *
     * @param  Item  $item
     * @return Quote
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;
        $item->setQuote($this);

        return $this;
    }

    /**
     * Removes an item
     *
     * @param  Item  $item
     * @return Quote
     */
    public function removeItem(Item $item)
    {
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Get items
     *
     * @return ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * @param string $terms
     *
     * @return Quote
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     *
     * @return Quote
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return float
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param float $tax
     *
     * @return Quote
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * PrePersist listener to link the items to the quote
     *
     * @ORM\PrePersist
     */
    public function updateTotal()
    {
        if (count($this->items)) {
            foreach ($this->items as $item) {
                $item->setQuote($this);
            }
        }
    }
}
