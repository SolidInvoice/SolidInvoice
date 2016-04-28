<?php
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

namespace CSBill\PaymentBundle\Entity;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Exception\UnexpectedTypeException;
use CSBill\CoreBundle\Traits\Entity;
use CSBill\InvoiceBundle\Entity\Invoice;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Payum\Core\Model\Payment as BasePayment;
use Payum\Core\Model\PaymentInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="payments")
 * @ORM\Entity(repositoryClass="CSBill\PaymentBundle\Repository\PaymentRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 */
class Payment extends BasePayment implements PaymentInterface
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
    protected $details;
    protected $description;
    protected $number;
    protected $clientEmail;
    protected $clientId;
    /**
     * @ORM\ManyToOne(targetEntity="CSBill\InvoiceBundle\Entity\Invoice", inversedBy="payments")
     *
     * @var Invoice
     */
    private $invoice;
    /**
     * @ORM\ManyToOne(targetEntity="CSBill\ClientBundle\Entity\Client", inversedBy="payments")
     * @ORM\JoinColumn(name="client", fieldName="client")
     *
     * @var Client
     */
    private $client;
    /**
     * @ORM\ManyToOne(targetEntity="CSBill\PaymentBundle\Entity\PaymentMethod", inversedBy="payments")
     *
     * @var PaymentMethod
     */
    private $method;
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     */
    private $status;
    /**
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="completed", type="datetime", nullable=true)
     * @Assert\DateTime
     */
    private $completed;

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
     * @return Payment
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
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
     * @return string
     */
    public function getMessage()
    {
	return $this->message;
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
    public function setCompleted(\DateTime $completed)
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
	return $this->getClient()->getId();
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
