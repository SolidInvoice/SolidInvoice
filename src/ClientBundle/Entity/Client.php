<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\PaymentBundle\Entity\Payment;
use CSBill\QuoteBundle\Entity\Quote;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serialize;
use Money\Currency;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="clients")
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\ClientRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("name")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @Serialize\XmlRoot("client")
 * @Hateoas\Relation("self", href=@Hateoas\Route("get_client", absolute=true, parameters={"clientId" : "expr(object.getId())"}))
 * @Hateoas\Relation("client.contacts", href=@Hateoas\Route("get_client_contacts", parameters={"clientId" : "expr(object.getId())"}, absolute=true))
 * @Hateoas\Relation("client.invoices", href=@Hateoas\Route("get_client_invoices", parameters={"clientId" : "expr(object.getId())"}, absolute=true))
 * @Hateoas\Relation("client.quotes",   href=@Hateoas\Route("get_client_quotes",   parameters={"clientId" : "expr(object.getId())"}, absolute=true))
 * @Hateoas\Relation("client.payments", href=@Hateoas\Route("get_client_payments", parameters={"clientId" : "expr(object.getId())"}, absolute=true))
 */
class Client
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
     * @Serialize\Groups({"api", "js"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=125, nullable=false, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"api", "js"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=125, nullable=true)
     * @Assert\Url()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"api", "js"})
     */
    private $website;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"api", "js"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $currency;

    /**
     * @var Collection|Contact[]
     *
     * @ORM\OneToMany(targetEntity="Contact", mappedBy="client", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     * @ORM\OrderBy({"firstName" = "ASC"})
     * @Assert\Count(min=1, minMessage="You need to add at least one contact to this client")
     * @Assert\Valid()
     * @Serialize\Groups({"js"})
     */
    private $contacts;

    /**
     * @var Collection|Quote[]
     *
     * @ORM\OneToMany(targetEntity="CSBill\QuoteBundle\Entity\Quote", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\OrderBy({"created" = "DESC"})
     * @Serialize\Groups({"none"})
     */
    private $quotes;

    /**
     * @var Collection|Invoice[]
     *
     * @ORM\OneToMany(targetEntity="CSBill\InvoiceBundle\Entity\Invoice", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\OrderBy({"created" = "DESC"})
     * @Serialize\Groups({"none"})
     */
    private $invoices;

    /**
     * @var Collection|Payment[]
     *
     * @ORM\OneToMany(targetEntity="CSBill\PaymentBundle\Entity\Payment", mappedBy="client", cascade={"persist", "remove"})
     * @Serialize\Groups({"none"})
     */
    private $payments;

    /**
     * @var Collection|Address[]
     *
     * @ORM\OneToMany(targetEntity="CSBill\ClientBundle\Entity\Address", mappedBy="client", cascade={"persist", "remove"})
     * @Serialize\Groups({"js"})
     */
    private $addresses;

    /**
     * @var Credit
     *
     * @ORM\OneToOne(targetEntity="CSBill\ClientBundle\Entity\Credit", mappedBy="client", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     * @Serialize\Groups({"api", "js"})
     * @Serialize\Inline()
     */
    private $credit;

    /**
     * Constructer.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->quotes = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->addresses = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Client
     */
    public function setName(string $name): Client
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return Client
     */
    public function setStatus(string $status): Client
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get website.
     *
     * @return string
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * Set website.
     *
     * @param string $website
     *
     * @return Client
     */
    public function setWebsite(string $website): Client
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Add contact.
     *
     * @param Contact $contact
     *
     * @return Client
     */
    public function addContact(Contact $contact): Client
    {
        $this->contacts[] = $contact;
        $contact->setClient($this);

        return $this;
    }

    /**
     * Removes a contact.
     *
     * @param Contact $contact
     *
     * @return Client
     */
    public function removeContact(Contact $contact): Client
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * Get contacts.
     *
     * @return Collection|Contact[]
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add quote.
     *
     * @param Quote $quote
     *
     * @return Client
     */
    public function addQuote(Quote $quote): Client
    {
        $this->quotes[] = $quote;
        $quote->setClient($this);

        return $this;
    }

    /**
     * Remove quote.
     *
     * @param Quote $quote
     *
     * @return Client
     */
    public function removeQuote(Quote $quote): Client
    {
        $this->quotes->removeElement($quote);

        return $this;
    }

    /**
     * Get quotes.
     *
     * @return Collection|Quote[]
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    /**
     * Add invoice.
     *
     * @param Invoice $invoice
     *
     * @return Client
     */
    public function addInvoice(Invoice $invoice): Client
    {
        $this->invoices[] = $invoice;
        $invoice->setClient($this);

        return $this;
    }

    /**
     * Remove invoice.
     *
     * @param Invoice $invoice
     *
     * @return Client
     */
    public function removeInvoice(Invoice $invoice): Client
    {
        $this->invoices->removeElement($invoice);

        return $this;
    }

    /**
     * Get invoices.
     *
     * @return Collection|Invoice[]
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    /**
     * Add payment.
     *
     * @param Payment $payment
     *
     * @return Client
     */
    public function addPayment(Payment $payment): Client
    {
        $this->payments[] = $payment;
        $payment->setClient($this);

        return $this;
    }

    /**
     * Removes a payment.
     *
     * @param Payment $payment
     *
     * @return Client
     */
    public function removePayment(Payment $payment): Client
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Get payments.
     *
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    /**
     * Add address.
     *
     * @param Address $address
     *
     * @return Client
     */
    public function addAddress(Address $address = null): Client
    {
        if (null !== $address) {
            $this->addresses[] = $address;
            $address->setClient($this);
        }

        return $this;
    }

    /**
     * Removes an address.
     *
     * @param Address $address
     *
     * @return Client
     */
    public function removeAddress(Address $address): Client
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Get addresses.
     *
     * @return Collection|Address[]
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    /**
     * @return Credit
     */
    public function getCredit(): Credit
    {
        return $this->credit;
    }

    /**
     * @param Credit $credit
     */
    public function setCredit(Credit $credit)
    {
        $this->credit = $credit;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setInitialCredit()
    {
        if (null === $this->id) {
            $credit = new Credit();
            $credit->setClient($this);
            $this->setCredit($credit);
        }
    }

    /**
     * Return the client name as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name ?? '';
    }

    /**
     * @return Currency|null
     */
    public function getCurrency()
    {
        return $this->currency ? new Currency($this->currency) : null;
    }

    /**
     * @param string $currency
     *
     * @return Client
     */
    public function setCurrency(string $currency): Client
    {
        $this->currency = $currency;

        return $this;
    }
}
