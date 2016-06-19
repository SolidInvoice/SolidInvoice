<?php

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
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serialize;
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Contact", mappedBy="client", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     * @ORM\OrderBy({"firstname" = "ASC"})
     * @Assert\Count(min=1, minMessage="You need to add at least one contact to this client")
     * @Serialize\Groups({"js"})
     */
    private $contacts;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CSBill\QuoteBundle\Entity\Quote", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\OrderBy({"created" = "DESC"})
     * @Serialize\Groups({"none"})
     */
    private $quotes;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CSBill\InvoiceBundle\Entity\Invoice", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\OrderBy({"created" = "DESC"})
     * @Serialize\Groups({"none"})
     */
    private $invoices;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CSBill\PaymentBundle\Entity\Payment", mappedBy="client", cascade={"persist", "remove"})
     * @Serialize\Groups({"none"})
     */
    private $payments;

    /**
     * @var ArrayCollection
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
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
    public function setName($name)
    {
        $this->name = $name;

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
     * @return Client
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get website.
     *
     * @return string
     */
    public function getWebsite()
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
    public function setWebsite($website)
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
    public function addContact(Contact $contact)
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
    public function removeContact(Contact $contact)
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * Get contacts.
     *
     * @return ArrayCollection
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
    public function addQuote(Quote $quote)
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
    public function removeQuote(Quote $quote)
    {
        $this->quotes->removeElement($quote);

        return $this;
    }

    /**
     * Get quotes.
     *
     * @return ArrayCollection
     */
    public function getQuotes()
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
    public function addInvoice(Invoice $invoice)
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
    public function removeInvoice(Invoice $invoice)
    {
        $this->invoices->removeElement($invoice);

        return $this;
    }

    /**
     * Get invoices.
     *
     * @return ArrayCollection
     */
    public function getInvoices()
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
    public function addPayment(Payment $payment)
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
    public function removePayment(Payment $payment)
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Get payments.
     *
     * @return ArrayCollection
     */
    public function getPayments()
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
    public function addAddress(Address $address = null)
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
    public function removeAddress(Address $address)
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Get addresses.
     *
     * @return ArrayCollection
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @return Credit
     */
    public function getCredit()
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
    public function __toString()
    {
        return $this->name;
    }
}
