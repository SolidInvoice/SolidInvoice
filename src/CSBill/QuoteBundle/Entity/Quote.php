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
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use CS\ClientBundle\Entity\Client;

/**
 * CSBill\ClientBundle\Entity\Quote
 *
 * @ORM\Table(name="quotes")
 * @ORM\Entity()
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable(fieldName="deleted")
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
     * @var Status $status
     *
     * @ORM\ManyToOne(targetEntity="Status", inversedBy="quotes")
     * @Assert\Valid()
     */
    private $status;

    /**
     * @var Client $client
     *
     * @ORM\ManyToOne(targetEntity="CS\ClientBundle\Entity\Client", inversedBy="quotes")
     * @Assert\Valid()
     */
    private $client;

    /**
     * @var float
     *
     * @ORM\Column(name="total", type="float")
     */
    private $total;

    /**
     * @var DateTime $due
     *
     * @ORM\Column(name="due", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $due;

    /**
     * @var DateTIme $created
     *
     * @ORM\Column(name="created", type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @Assert\DateTime()
     */
    private $created;

    /**
     * @var DateTime $updated
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Assert\DateTime()
     */
    private $updated;

    /**
     * @var DateTime $deleted
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $deleted;

    /**
     * @var ArrayCollection $items
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="quote", cascade={"ALL"})
     * @Orm\OrderBy({"name" = "ASC"})
     * @Assert\Valid()
     * @Assert\Count(min=1)
     */
    private $items;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->items = new ArrayCollection;
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
     * @param  Client $client
     * @return Quote
     */
    public function setClient(Client $client)
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
}
