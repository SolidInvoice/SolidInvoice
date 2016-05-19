<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Entity;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Traits\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serialize;
use Money\Money;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="quotes")
 * @ORM\Entity(repositoryClass="CSBill\QuoteBundle\Repository\QuoteRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @ORM\HasLifecycleCallbacks()
 * @Serialize\ExclusionPolicy("all")
 * @Serialize\XmlRoot("quote")
 * @Hateoas\Relation("self", href=@Hateoas\Route("get_quotes", absolute=true))
 */
class Quote
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable,
        Entity\Archivable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Expose
     */
    private $id;

    /**
     * @var Uuid
     *
     * @ORM\Column(name="uuid", type="uuid", length=36)
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Expose()
     */
    private $status;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="CSBill\ClientBundle\Entity\Client", inversedBy="quotes")
     * @Assert\NotBlank
     */
    private $client;

    /**
     * @var Money
     *
     * @ORM\Column(name="total", type="money")
     * @Serialize\Expose()
     */
    private $total;

    /**
     * @var Money
     *
     * @ORM\Column(name="base_total", type="money")
     * @Serialize\Expose()
     */
    private $baseTotal;

    /**
     * @var Money
     *
     * @ORM\Column(name="tax", type="money", nullable=true)
     * @Serialize\Expose()
     */
    private $tax;

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="float", nullable=true)
     * @Serialize\Expose()
     */
    private $discount;

    /**
     * @var string
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     * @Serialize\Expose()
     */
    private $terms;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     * @Serialize\Expose()
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due", type="date", nullable=true)
     * @Assert\DateTime
     * @Serialize\Exclude()
     */
    private $due;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="quote", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Quote")
     * @Serialize\Expose()
     */
    private $items;

    /**
     * @ORM\Column(name="users", type="array", nullable=false)
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Quote")
     *
     * @var ArrayCollection
     */
    private $users;

    /**
     * Constructer.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->setUuid(Uuid::uuid1());
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param Uuid $uuid
     *
     * @return Quote
     */
    public function setUuid(Uuid $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Return users array.
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param array $users
     *
     * @return Quote
     */
    public function setUsers(array $users = array())
    {
        $this->users = new ArrayCollection($users);

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
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
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get Client.
     *
     * @return Client
     */
    public function getClient()
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
    public function setClient(Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get total.
     *
     * @return Money
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set total.
     *
     * @param Money $total
     *
     * @return Quote
     */
    public function setTotal(Money $total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get base total.
     *
     * @return Money
     */
    public function getBaseTotal()
    {
        return $this->baseTotal;
    }

    /**
     * Set base total.
     *
     * @param Money $baseTotal
     *
     * @return Quote
     */
    public function setBaseTotal(Money $baseTotal)
    {
        $this->baseTotal = $baseTotal;

        return $this;
    }

    /**
     * Get discount.
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set discount.
     *
     * @param float $discount
     *
     * @return Quote
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get due.
     *
     * @return \DateTime
     */
    public function getDue()
    {
        return $this->due;
    }

    /**
     * Set due.
     *
     * @param \DateTime $due
     *
     * @return Quote
     */
    public function setDue(\DateTime $due)
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Add item.
     *
     * @param Item $item
     *
     * @return Quote
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;
        $item->setQuote($this);

        return $this;
    }

    /**
     * Removes an item.
     *
     * @param Item $item
     *
     * @return Quote
     */
    public function removeItem(Item $item)
    {
        $this->items->removeElement($item);
        $item->setQuote(null);

        return $this;
    }

    /**
     * Get items.
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
     * @return Money
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param Money $tax
     *
     * @return Quote
     */
    public function setTax(Money $tax)
    {
        $this->tax = $tax;

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
