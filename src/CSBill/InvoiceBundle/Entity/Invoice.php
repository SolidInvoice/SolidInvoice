<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as Grid;
use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Traits\Entity;
use CSBill\PaymentBundle\Entity\Payment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="invoices")
 * @ORM\Entity(repositoryClass="CSBill\InvoiceBundle\Repository\InvoiceRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @ORM\HasLifecycleCallbacks()
 */
class Invoice
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Uuid
     *
     * @ORM\Column(name="uuid", type="uuid", length=36)
     */
    private $uuid;

    /**
     * @var Status $status
     *
     * @ORM\ManyToOne(targetEntity="Status", inversedBy="invoices")
     * @Grid\Column(name="status", field="status.name", title="status", filter="select", selectFrom="source")
     * @Grid\Column(field="status.label", visible=false)
     */
    private $status;

    /**
     * @var Client $client
     *
     * @ORM\ManyToOne(targetEntity="CSBill\ClientBundle\Entity\Client", inversedBy="invoices")
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
     * @var \DateTime $paidDate
     *
     * @ORM\Column(name="paid_date", type="datetime", nullable=true)
     * @Assert\DateTime
     */
    private $paidDate;

    /**
     * @var ArrayCollection $items
     *
     * @ORM\OneToMany(targetEntity="Item", mappedBy="invoice", cascade={"persist"})
     * @Orm\OrderBy({"description" = "ASC"})
     * @Assert\Valid
     * @Assert\Count(min=1, minMessage="You need to add at least 1 item to the Invoice")
     */
    private $items;

    /**
     * @var ArrayCollection $payments
     *
     * @ORM\OneToMany(
     *     targetEntity="CSBill\PaymentBundle\Entity\Payment",
     *     mappedBy="invoice",
     *     cascade={"persist"}
     * )
     */
    private $payments;

    /**
     * @ORM\Column(name="users", type="array", nullable=false)
     * @Assert\Count(min=1, minMessage="You need to select at least 1 user to attach to the Invoice")
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
        $this->payments = new ArrayCollection;
        $this->users = new ArrayCollection;
        $this->setUuid(Uuid::uuid1());
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
     * @return Uuid
     */
    public function getUuid()
    {
        return $this->uuid;
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
     * @param  Status  $status
     * @return Invoice
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
     * @param  Client  $client
     * @return Invoice
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
     * @param  float   $total
     * @return Invoice
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
     * @param  float   $discount
     * @return Invoice
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
     * @return Invoice
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
     * Set paidDate
     *
     * @param  \DateTime $paidDate
     * @return Invoice
     */
    public function setPaidDate(\DateTime $paidDate)
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    /**
     * Get paidDate
     *
     * @return \DateTime
     */
    public function getPaidDate()
    {
        return $this->paidDate;
    }

    /**
     * Add item
     *
     * @param  Item    $item
     * @return Invoice
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;
        $item->setInvoice($this);

        return $this;
    }

    /**
     * Removes an item
     *
     * @param  Item    $item
     * @return Invoice
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
     * Add payment
     *
     * @param  Payment $payment
     * @return Invoice
     */
    public function addPayment(Payment $payment)
    {
        $this->payments[] = $payment;
        $payment->setInvoice($this);

        return $this;
    }

    /**
     * Removes a payment
     *
     * @param Payment $payment
    *
     * @return Invoice
     */
    public function removePayment(Payment $payment)
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Get payments
     *
     * @return ArrayCollection
     */
    public function getPayments()
    {
        return $this->payments;
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
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
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
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
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
     * @return Invoice
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * PrePersist listener to update the invoice total
     *
     * @ORM\PrePersist
     */
    public function updateItems()
    {
        if (count($this->items)) {
            foreach ($this->items as $item) {
                $item->setInvoice($this);
            }
        }
    }
}
