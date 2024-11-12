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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serialize;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    types: ['https://schema.org/Corporation'],
    operations: [
        new Get(),
        new Post(),
        new GetCollection(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: [
        'groups' => ['client_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
        AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
    ],
    denormalizationContext: [
        'groups' => ['client_api:write'],
    ],
    validationContext: [
        'groups' => ['Default', 'api'],
    ],
)]
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

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Serialize\Groups(['client_api:read'])]
    private ?Ulid $id = null;

    #[ApiProperty(iris: ['https://schema.org/name'])]
    #[ORM\Column(name: 'name', type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 125)]
    #[Serialize\Groups(['client_api:read', 'client_api:write'])]
    private ?string $name = null;

    #[ApiProperty(iris: ['https://schema.org/URL'])]
    #[ORM\Column(name: 'website', type: Types::STRING, length: 125, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 125)]
    #[Serialize\Groups(['client_api:read', 'client_api:write'])]
    private ?string $website = null;

    #[ApiProperty(writable: false, iris: ['https://schema.org/Text'])]
    #[ORM\Column(name: 'status', type: Types::STRING, length: 25)]
    #[Serialize\Groups(['client_api:read'])]
    private ?string $status = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: true)]
    #[Serialize\Groups(['client_api:read', 'client_api:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => [
                'oneOf' => [
                    ['type' => 'string'],
                    ['type' => 'null'],
                ],
            ],
        ],
        jsonSchemaContext: [
            'type' => [
                'oneOf' => [
                    ['type' => 'string'],
                    ['type' => 'null'],
                ],
            ],
        ],
        // property: 'currency'
    )
    ]
    private ?string $currencyCode = null;

    private Currency $currency;

    #[ORM\Column(name: 'vat_number', type: Types::STRING, nullable: true)]
    #[Serialize\Groups(['client_api:read', 'client_api:write'])]
    private ?string $vatNumber = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ApiProperty(example: ['/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6/contact/3fa85f64-5717-4562-b3fc-2c963f66afa6'], iris: ['https://schema.org/Person'])]
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Contact::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[Assert\Count(
        min: 1,
        minMessage: 'You need to add at least one contact to this client',
        // Only validate from the form.
        // API validation should not happen, since you should be able to
        // create clients without contacts
        // (Due to the REST nature of the API, the contacts are a separate resource)
        groups: ['form'],
    )]
    #[Assert\Valid(groups: ['form'])]
    #[Serialize\Groups(['client_api:read'])]
    private Collection $contacts;

    /**
     * @var Collection<int, Quote>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Quote::class, cascade: ['remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC'])]
    #[Serialize\Groups(['client_api:read'])]
    #[ApiProperty(example: ['/api/quotes/3fa85f64-5717-4562-b3fc-2c963f66afa6'])]
    private Collection $quotes;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Invoice::class, cascade: ['remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC'])]
    #[Serialize\Groups(['client_api:read'])]
    #[ApiProperty(example: ['/api/invoices/3fa85f64-5717-4562-b3fc-2c963f66afa6'])]
    private Collection $invoices;

    /**
     * @var Collection<int, RecurringInvoice>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: RecurringInvoice::class, cascade: ['remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC'])]
    #[Serialize\Groups(['client_api:read'])]
    #[ApiProperty(example: ['/api/recurring-invoices/3fa85f64-5717-4562-b3fc-2c963f66afa6'])]
    private Collection $recurringInvoices;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Payment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Serialize\Groups(['client_api:read'])]
    #[ApiProperty(example: ['/api/payments/3fa85f64-5717-4562-b3fc-2c963f66afa6'])]
    private Collection $payments;

    /**
     * @var Collection<int, Address>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Address::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Serialize\Groups(['client_api:read'])]
    #[ApiProperty(example: ['/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6/address/3fa85f64-5717-4562-b3fc-2c963f66afa6'])]
    private Collection $addresses;

    #[ApiProperty(
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ],
        iris: ['https://schema.org/MonetaryAmount']
    )]
    #[ORM\OneToOne(mappedBy: 'client', targetEntity: Credit::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[Serialize\Groups(['client_api:read', 'client_api:write'])]
    private ?Credit $credit = null;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->quotes = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->recurringInvoices = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->addresses = new ArrayCollection();

        $this->setCredit(new Credit());
    }

    public function getId(): ?Ulid
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

    public function getCredit(): ?Credit
    {
        return $this->credit;
    }

    public function setCredit(Credit $credit): self
    {
        $this->credit = $credit;
        $credit->setClient($this);

        return $this;
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
