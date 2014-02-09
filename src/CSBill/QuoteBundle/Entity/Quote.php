<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
 * @ORM\Entity()
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
     * @ORM\Column(name="discount", type="float", nullable=true)
     */
    private $discount;

    /**
     * @var DateTime $due
     *
     * @ORM\Column(name="due", type="date", nullable=true)
     * @Assert\DateTime
     */
    private $due;

    /**
     * @var DateTIme $created
     *
     * @ORM\Column(name="created", type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @Assert\DateTime
     */
    private $created;

    /**
     * @var DateTime $updated
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Assert\DateTime
     */
    private $updated;

    /**
     * @var DateTime $deleted
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
     * @param  float   $baseTotal
     * @return Invoice
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
     * PrePersist listener to update the quote total
     *
     * @ORM\PrePersist
     */
    public function updateTotal()
    {
        if (count($this->items)) {
            $total = 0;
            foreach ($this->items as $item) {
                $item->setQuote($this);
                $total += ($item->getPrice() * $item->getQty());
            }

            $this->setBaseTotal($total);

            if ($this->discount > 0) {
                $total -= ($total * $this->discount) / 100;
            }

            $this->setTotal($total);
        } else {
            $this->setBaseTotal(0)
                 ->setTotal(0);
        }
    }
}
