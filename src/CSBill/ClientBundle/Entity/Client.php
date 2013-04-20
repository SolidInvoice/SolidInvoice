<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use APY\DataGridBundle\Grid\Mapping as GRID;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\InvoiceBundle\Entity\Invoice;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="clients")
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\ClientRepository")
 * @UniqueEntity("name")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable(fieldName="deleted")
 */
class Client
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
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=125, nullable=false, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     */
    private $name;

    /**
     * @var string $website
     *
     * @ORM\Column(name="website", type="string", length=125, nullable=true)
     * @Assert\Url()
     * @Assert\Length(max=125)
     */
    private $website;

    /**
     * @var Status $status
     *
     * @ORM\ManyToOne(targetEntity="Status", inversedBy="clients")
     * @Assert\Valid()
     * @GRID\Column(field="status.name", filter="source", filter="select", selectFrom="source", title="status")
     */
    private $status;

    /**
     * @var ArrayCollection $contacts
     *
     * @ORM\OneToMany(targetEntity="Contact", mappedBy="client", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"firstname" = "ASC"})
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="You need to add at least one contact to this client")
     */
    private $contacts;

    /**
     * @var ArrayCollection $quotes
     *
     * @ORM\OneToMany(targetEntity="CSBill\QuoteBundle\Entity\Quote", mappedBy="client")
     * @Assert\Valid()
     */
    private $quotes;

    /**
     * @var ArrayCollection $invoices
     *
     * @ORM\OneToMany(targetEntity="CSBill\InvoiceBundle\Entity\Invoice", mappedBy="client")
     * @Assert\Valid()
     */
    private $invoices;

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
     * Constructer
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection;
        $this->quotes = new ArrayCollection;
        $this->invoices = new ArrayCollection;
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
     * Set name
     *
     * @param  string $name
     * @return Client
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set status
     *
     * @param  string $status
     * @return Client
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set website
     *
     * @param  string $website
     * @return Client
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Add contact
     *
     * @param  Contact $contact
     * @return Client
     */
    public function addContact(Contact $contact)
    {
        $this->contacts[] = $contact;
        $contact->setClient($this);

        return $this;
    }

    /**
     * Removes a contact
     *
     * @param  Contact $contact
     * @return Client
     */
    public function removeContact(Contact $contact)
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * Get contacts
     *
     * @return ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add quote
     *
     * @param  Quote  $quote
     * @return Client
     */
    public function addQuote(Quote $quote)
    {
        $this->quotes[] = $quote;
        $quote->setClient($this);

        return $this;
    }

    /**
     * Remove quote
     *
     * @param  Quote  $quote
     * @return Client
     */
    public function removeQuote(Quote $quote)
    {
        $this->quotes->removeElement($quote);

        return $this;
    }

    /**
     * Get quotes
     *
     * @return ArrayCollection
     */
    public function getQuotes()
    {
        return $this->quotes;
    }

    /**
     * Add invoice
     *
     * @param  Invoice  $invoice
     * @return Client
     */
    public function addInvoice(Invoice $invoice)
    {
        $this->invoices[] = $invoice;
        $invoice->setClient($this);

        return $this;
    }

    /**
     * Remove invoice
     *
     * @param  Invoice  $invoice
     * @return Client
     */
    public function removeInvoice(Invoice $invoice)
    {
        $this->invoices->removeElement(invoice);

        return $this;
    }

    /**
     * Get invoices
     *
     * @return ArrayCollection
     */
    public function getInvoices()
    {
        return $this->invoices;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * Return the client name as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
