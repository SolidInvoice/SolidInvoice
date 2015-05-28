<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as Grid;
use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Exception\UnexpectedTypeException;
use CSBill\CoreBundle\Traits\Entity;
use CSBill\InvoiceBundle\Entity\Invoice;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="payments")
 * @ORM\Entity(repositoryClass="CSBill\PaymentBundle\Repository\PaymentRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @GRID\Source(groupBy="created")
 */
class Payment
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="CSBill\InvoiceBundle\Entity\Invoice", inversedBy="payments")
     * @Grid\Column(name="invoice", field="invoice.id", title="Invoice", filter="select", selectFrom="source")
     *
     * @var Invoice
     */
    private $invoice;

    /**
     * @ORM\ManyToOne(targetEntity="CSBill\ClientBundle\Entity\Client", inversedBy="payments")
     * @Grid\Column(type="client", name="client", field="client.name", title="Client", filter="select", selectFrom="source", joinType="inner")
     * @Grid\Column(field="client.id", visible=false, joinType="inner")
     *
     * @var Client
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="CSBill\PaymentBundle\Entity\PaymentMethod", inversedBy="payments")
     * @Grid\Column(name="method", field="method.name", title="Method", filter="select", selectFrom="source")
     *
     * @var PaymentMethod
     */
    private $method;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Grid\Column(name="status", type="status", title="status", filter="select", selectFrom="source", label_function="payment_label")
     */
    private $status;

    /**
     * @ORM\Column(name="amount", type="float")
     * @Grid\Column(type="currency", title="Total")
     */
    private $totalAmount;

    /**
     * @ORM\Column(name="currency", type="string", length=24)
     * @Grid\Column(title="currency")
     */
    private $currencyCode;

    /**
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var array
     *
     * @ORM\Column(name="details", type="array", nullable=true)
     * @Grid\Column(visible=false)
     */
    private $details;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="completed", type="datetime", nullable=true)
     * @Assert\DateTime
     * @Grid\Column(sortable=true, order="desc")
     */
    private $completed;

    public function __construct()
    {
        $this->details = array();
    }

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Invoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Payment
     */
    public function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * @return PaymentMethod
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param PaymentMethod $method
     *
     * @return Payment
     */
    public function setMethod(PaymentMethod $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return Payment
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
     * Set details.
     *
     * @param array|\Traversable $details
     *
     * @return Payment
     *
     * @throws UnexpectedTypeException
     */
    public function setDetails($details)
    {
        if ($details instanceof \Traversable) {
            $details = iterator_to_array($details);
        }

        if (!is_array($details)) {
            throw new UnexpectedTypeException($details, 'array');
        }

        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set currency.
     *
     * @param float $currencyCode
     *
     * @return Payment
     */
    public function setCurrencyCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return float
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Set amount.
     *
     * @param float $totalAmount
     *
     * @return Payment
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param string $message
     *
     * @return Payment
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \DateTime
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * @param \DateTime $completed
     *
     * @return Payment
     */
    public function setCompleted(\DateTIme $completed)
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     *
     * @return Payment
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }
}
