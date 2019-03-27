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

namespace SolidInvoice\ClientBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use SolidInvoice\CoreBundle\Traits\Entity;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Currency;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(attributes={"normalization_context"={"groups"={"client_api"}}, "denormalization_context"={"groups"={"client_api"}}}, iri="https://schema.org/Corporation")
 * @ORM\Table(name="clients")
 * @ORM\Entity(repositoryClass="SolidInvoice\ClientBundle\Repository\ClientRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("name")
 * @Gedmo\Loggable()
 */
class Client
{
    use Entity\TimeStampable,
        Entity\Archivable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"client_api"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=125, nullable=false, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=125, nullable=true)
     * @Assert\Url()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="https://schema.org/URL")
     */
    private $website;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=25)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="http://schema.org/Text")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="http://schema.org/Text")
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="vat_number", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private $vatNumber;

    /**
     * @var Collection|Contact[]
     *
     * @ORM\OneToMany(targetEntity="Contact", mappedBy="client", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"firstName" = "ASC"})
     * @Assert\Count(min=1, minMessage="You need to add at least one contact to this client")
     * @Assert\Valid()
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="http://schema.org/Person")
     */
    private $contacts;

    /**
     * @var Collection|Quote[]
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\QuoteBundle\Entity\Quote", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     * @ApiSubresource
     */
    private $quotes;

    /**
     * @var Collection|Invoice[]
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\InvoiceBundle\Entity\Invoice", mappedBy="client", fetch="EXTRA_LAZY", cascade={"remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     * @ApiSubresource
     */
    private $invoices;

    /**
     * @var Collection|Payment[]
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\PaymentBundle\Entity\Payment", mappedBy="client", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ApiSubresource
     */
    private $payments;

    /**
     * @var Collection|Address[]
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\ClientBundle\Entity\Address", mappedBy="client", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Serialize\Groups({"client_api"})
     */
    private $addresses;

    /**
     * @var Credit
     *
     * @ORM\OneToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Credit", mappedBy="client", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Serialize\Groups({"client_api"})
     * @ApiProperty(iri="http://schema.org/MonetaryAmount")
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
    public function getId(): ?int
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
    public function setName(string $name): self
    {
        $this->name = $name;

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
     * @param string $status
     *
     * @return Client
     */
    public function setStatus(string $status): self
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
    public function setWebsite(string $website): self
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
    public function addContact(Contact $contact): self
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
    public function removeContact(Contact $contact): self
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * Get contacts.
     *
     * @return Collection|Contact[]
     */
    public function getContacts(): Collection
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
    public function addQuote(Quote $quote): self
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
    public function removeQuote(Quote $quote): self
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
    public function addInvoice(Invoice $invoice): self
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
    public function removeInvoice(Invoice $invoice): self
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
    public function addPayment(Payment $payment): self
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
    public function removePayment(Payment $payment): self
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
    public function addAddress(?Address $address): self
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
    public function removeAddress(Address $address): self
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
    public function getCredit(): ?Credit
    {
        return $this->credit;
    }

    /**
     * @param Credit $credit
     *
     * @return Client
     */
    public function setCredit(Credit $credit): self
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     * @ApiProperty(iri="http://schema.org/MonetaryAmount")
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
    public function getCurrency(): ?Currency
    {
        return $this->currency ? new Currency($this->currency) : null;
    }

    /**
     * @param string $currency
     *
     * @return Client
     */
    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    /**
     * @param string $vatNumber
     *
     * @return $this
     */
    public function setVatNumber(?string $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }
}
