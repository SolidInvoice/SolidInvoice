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

namespace SolidInvoice\PaymentBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Payum\Core\Model\Payment as BasePayment;
use Payum\Core\Model\PaymentInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Exception\UnexpectedTypeException;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;
use Traversable;

/**
 * @ApiResource(collectionOperations={"get"={"method"="GET"}}, itemOperations={"get"={"method"="GET"}}, attributes={"normalization_context"={"groups"={"payment_api"}}})
 * @ORM\Table(name="payments")
 * @ORM\Entity(repositoryClass="SolidInvoice\PaymentBundle\Repository\PaymentRepository")
 * @Gedmo\Loggable
 */
class Payment extends BasePayment implements PaymentInterface
{
    use TimeStampable;

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
     * @ORM\ManyToOne(targetEntity="SolidInvoice\InvoiceBundle\Entity\Invoice", inversedBy="payments")
     *
     * @var Invoice
     */
    private $invoice;

    /**
     * @ORM\ManyToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="payments")
     * @ORM\JoinColumn(name="client", fieldName="client")
     *
     * @var Client
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="SolidInvoice\PaymentBundle\Entity\PaymentMethod", inversedBy="payments")
     *
     * @var PaymentMethod
     * @Serialize\Groups({"payment_api", "client_api"})
     */
    private $method;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"payment_api", "client_api"})
     */
    private $status;

    /**
     * @ORM\Column(name="message", type="text", nullable=true)
     * @Serialize\Groups({"payment_api", "client_api"})
     */
    private $message;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="completed", type="datetime", nullable=true)
     * @Assert\DateTime
     * @Serialize\Groups({"payment_api", "client_api"})
     */
    private $completed;

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Invoice
     */
    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    /**
     * @return Payment
     */
    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * @return PaymentMethod
     */
    public function getMethod(): ?PaymentMethod
    {
        return $this->method;
    }

    /**
     * @return Payment
     */
    public function setMethod(PaymentMethod $method): self
    {
        $this->method = $method;

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
     * @return Payment
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set details.
     *
     * @param array|Traversable $details
     *
     * @return Payment
     *
     * @throws UnexpectedTypeException
     */
    public function setDetails($details): self
    {
        if ($details instanceof Traversable) {
            $details = iterator_to_array($details);
        }

        if (!is_array($details)) {
            throw new UnexpectedTypeException((string) $details, 'array or \Traversable');
        }

        $this->details = $details;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return Payment
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCompleted(): ?DateTime
    {
        return $this->completed;
    }

    /**
     * @return Payment
     */
    public function setCompleted(DateTime $completed): self
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * @return int
     */
    public function getClientId(): ?int
    {
        $client = $this->getClient();

        return null !== $client ? $client->getId() : null;
    }

    /**
     * @return Client
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * @return Payment
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }
}
