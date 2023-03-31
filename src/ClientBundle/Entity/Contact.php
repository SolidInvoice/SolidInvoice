<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Serializable;
use SolidInvoice\CoreBundle\Doctrine\Id\IdGenerator;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceContact;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceContact;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Entity\QuoteContact;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;
use function strtolower;

/**
 * @ApiResource(attributes={"normalization_context"={"groups"={"contact_api"}}, "denormalization_context"={"groups"={"contact_api"}}}, collectionOperations={"post"={"method"="POST"}}, iri="https://schema.org/Person")
 * @ORM\Table(name="contacts", indexes={@ORM\Index(columns={"email"})})
 * @ORM\Entity(repositoryClass="SolidInvoice\ClientBundle\Repository\ContactRepository")
 * @Gedmo\Loggable
 */
class Contact implements Serializable
{
    use TimeStampable;
    use CompanyAware;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=IdGenerator::class)
     * @Serialize\Groups({"client_api", "contact_api"})
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="firstName", type="string", length=125)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"client_api", "contact_api"})
     * @ApiProperty(iri="https://schema.org/givenName")
     */
    private $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="lastName", type="string", length=125, nullable=true)
     * @Assert\Length(max=125)
     * @Serialize\Groups({"client_api", "contact_api"})
     * @ApiProperty(iri="https://schema.org/familyName")
     */
    private $lastName;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="contacts")
     * @ORM\JoinColumn(name="client_id")
     * @Serialize\Groups({"contact_api"})
     * @ApiProperty(iri="https://schema.org/Organization")
     * @Assert\Valid()
     * @Assert\NotBlank()
     */
    private $client;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Email(mode="strict")
     * @Serialize\Groups({"client_api", "contact_api"})
     * @ApiProperty(iri="https://schema.org/email")
     */
    private $email;

    /**
     * @var Collection<int, AdditionalContactDetail>
     *
     * @ORM\OneToMany(targetEntity="AdditionalContactDetail", mappedBy="contact", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     * @Serialize\Groups({"client_api", "contact_api"})
     */
    private $additionalContactDetails;

    /**
     * @var Collection<int, InvoiceContact>
     *
     * @ORM\OneToMany(targetEntity=InvoiceContact::class, cascade={"persist", "remove"}, mappedBy="contact")
     */
    private Collection $invoices;

    /**
     * @var Collection<int, RecurringInvoiceContact>
     *
     * @ORM\OneToMany(targetEntity=RecurringInvoiceContact::class, cascade={"persist", "remove"}, mappedBy="contact")
     */
    private $recurringInvoices;

    /**
     * @var Collection<int, QuoteContact>
     *
     * @ORM\OneToMany(targetEntity=QuoteContact::class, cascade={"persist", "remove"}, mappedBy="contact")
     */
    private $quotes;

    public function __construct()
    {
        $this->additionalContactDetails = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->recurringInvoices = new ArrayCollection();
        $this->quotes = new ArrayCollection();
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
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get client.
     *
     * @return Client
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Add additional detail.
     */
    public function addAdditionalContactDetail(AdditionalContactDetail $detail): self
    {
        $this->additionalContactDetails->add($detail);
        $detail->setContact($this);

        return $this;
    }

    /**
     * Removes additional detail from the current contact.
     */
    public function removeAdditionalContactDetail(AdditionalContactDetail $detail): self
    {
        $this->additionalContactDetails->removeElement($detail);

        return $this;
    }

    /**
     * Get additional details.
     *
     * @return Collection<int, AdditionalContactDetail>
     */
    public function getAdditionalContactDetails(): Collection
    {
        return $this->additionalContactDetails;
    }

    public function getAdditionalContactDetail(string $type): ?AdditionalContactDetail
    {
        $type = strtolower($type);
        foreach ($this->additionalContactDetails as $detail) {
            if (strtolower((string) $detail->getType()) === $type) {
                return $detail;
            }
        }

        return null;
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @return list<string|int|DateTimeInterface|null>
     */
    public function __serialize(): array
    {
        return [$this->id, $this->firstName, $this->lastName, $this->created, $this->updated];
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }

    /**
     * @param array<string, string|int|DateTimeInterface|null> $data
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->firstName = $data['firstName'];
        $this->lastName = $data['lastName'];
        $this->created = $data['created'];
        $this->updated = $data['updated'];
    }

    public function __toString(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices->map(static fn (InvoiceContact $invoiceContact): Invoice => $invoiceContact->getInvoice());
    }

    /**
     * @return Collection<int, RecurringInvoice>
     */
    public function getRecurringInvoices(): Collection
    {
        return $this->recurringInvoices->map(static fn (RecurringInvoiceContact $recurringInvoiceContact): RecurringInvoice => $recurringInvoiceContact->getRecurringInvoice());
    }

    /**
     * @return Collection<int, Quote>
     */
    public function getQuotes(): Collection
    {
        return $this->quotes->map(static fn (QuoteContact $quoteContact): Quote => $quoteContact->getQuote());
    }
}
