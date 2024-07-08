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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(types: ['https://schema.org/Corporation'], normalizationContext: ['groups' => ['client_api']], denormalizationContext: ['groups' => ['client_api']])]
#[ORM\Table(name: Client::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['name', 'company_id'])]
#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('name')]
class Client implements Stringable
{
    final public const TABLE_NAME = 'clients';
    use Archivable;
    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[Serialize\Groups(['client_api'])]
    private ?UuidInterface $id = null;

    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[ORM\Column(name: 'name', type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 125)]
    #[Serialize\Groups(['client_api'])]
    private ?string $name = null;

    #[ApiProperty(iris: ['https://schema.org/URL'])]
    #[ORM\Column(name: 'website', type: Types::STRING, length: 125, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 125)]
    #[Serialize\Groups(['client_api'])]
    private ?string $website = null;

    #[ApiProperty(iris: ['https://schema.org/Text'])]
    #[ORM\Column(name: 'status', type: Types::STRING, length: 25)]
    #[Serialize\Groups(['client_api'])]
    private ?string $status = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: true)]
    private ?string $currencyCode = null;

    #[ApiProperty(iris: ['https://schema.org/Text'])]
    #[Serialize\Groups(['client_api'])]
    private Currency $currency;

    #[ORM\Column(name: 'vat_number', type: Types::STRING, nullable: true)]
    #[Serialize\Groups(['client_api'])]
    private ?string $vatNumber = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ApiProperty(iris: ['https://schema.org/Person'])]
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Contact::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[Assert\Count(min: 1, minMessage: 'You need to add at least one contact to this client')]
    #[Assert\Valid]
    #[Serialize\Groups(['client_api'])]
    private Collection $contacts;

    /**
     * @var Collection<int, Quote>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Quote::class, cascade: ['remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC'])]
    private Collection $quotes;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Invoice::class, cascade: ['remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC'])]
    private Collection $invoices;

    /**
     * @var Collection<int, RecurringInvoice>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: RecurringInvoice::class, cascade: ['remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC'])]
    private Collection $recurringInvoices;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Payment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $payments;

    /**
     * @var Collection<int, Address>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Address::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Serialize\Groups(['client_api'])]
    private Collection $addresses;

    #[ApiProperty(iris: ['https://schema.org/MonetaryAmount'])]
    #[ORM\OneToOne(mappedBy: 'client', targetEntity: Credit::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[Serialize\Groups(['client_api'])]
    private ?Credit $credit = null;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->quotes = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->recurringInvoices = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->addresses = new ArrayCollection();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function addContact(Contact $contact): self
    {
        $this->contacts[] = $contact;
        $contact->setClient($this);

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addQuote(Quote $quote): self
    {
        $this->quotes[] = $quote;
        $quote->setClient($this);

        return $this;
    }

    public function removeQuote(Quote $quote): self
    {
        $this->quotes->removeElement($quote);

        return $this;
    }

    /**
     * @return Collection<int, Quote>
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    public function addInvoice(Invoice $invoice): self
    {
        $this->invoices[] = $invoice;
        $invoice->setClient($this);

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        $this->invoices->removeElement($invoice);

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addRecurringInvoice(RecurringInvoice $invoice): self
    {
        $this->recurringInvoices[] = $invoice;
        $invoice->setClient($this);

        return $this;
    }

    public function removeRecurringInvoice(RecurringInvoice $invoice): self
    {
        $this->recurringInvoices->removeElement($invoice);

        return $this;
    }

    /**
     * @return Collection<int, RecurringInvoice>
     */
    public function getRecurringInvoices(): Collection
    {
        return $this->recurringInvoices;
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;
        $payment->setClient($this);

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addAddress(?Address $address): self
    {
        if ($address instanceof Address) {
            $this->addresses[] = $address;
            $address->setClient($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function getCredit(): Credit
    {
        return $this->credit;
    }

    public function setCredit(Credit $credit): self
    {
        $this->credit = $credit;

        return $this;
    }

    #[ORM\PrePersist]
    public function setInitialCredit(): void
    {
        if (! $this->id instanceof UuidInterface) {
            $credit = new Credit();
            $credit->setClient($this);
            $this->setCredit($credit);
        }
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getCurrency(): Currency
    {
        if (! isset($this->currency) && null !== $this->currencyCode) {
            $this->currency = new Currency($this->currencyCode);
        }

        return $this->currency;
    }

    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
