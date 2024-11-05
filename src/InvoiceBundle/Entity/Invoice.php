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

namespace SolidInvoice\InvoiceBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Traits\InvoiceStatusTrait;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Invoice::TABLE_NAME)]
#[ORM\Index(columns: ['quote_id'])]
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ApiResource(
    operations: [new GetCollection(), new Get(), new Post(), new Patch(), new Delete()],
    normalizationContext: [
        'groups' => ['invoice_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['invoice_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/invoices',
    operations: [new GetCollection()],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'invoices',
            fromClass: Client::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['invoice_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['invoice_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
class Invoice extends BaseInvoice implements Stringable
{
    final public const TABLE_NAME = 'invoices';
    use Archivable;
    use InvoiceStatusTrait {
        Archivable::isArchived insteadof InvoiceStatusTrait;
    }
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['invoice_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'invoice_id', type: Types::STRING, length: 255)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private string $invoiceId = '';

    #[ORM\Column(name: 'uuid', type: Types::STRING, length: 36)]
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(writable: false)]
    private ?string $uuid = null;

    #[ApiProperty(
        example: '/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6',
        iris: ['https://schema.org/Organization']
    )]
    #[ORM\ManyToOne(targetEntity: Client::class, cascade: ['persist'], inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private ?Client $client = null;

    #[ORM\Column(name: 'balance_amount', type: BigIntegerType::NAME)]
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    private BigNumber $balance;

    #[ORM\Column(name: 'due', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(type: DateTimeInterface::class)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private ?DateTimeInterface $due = null;

    #[ORM\Column(name: 'invoice_date', type: Types::DATE_IMMUTABLE, nullable: false)]
    #[Assert\Type(type: DateTimeInterface::class)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private DateTimeInterface $invoiceDate;

    #[ORM\Column(name: 'paid_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\Type(type: DateTimeInterface::class)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private ?DateTime $paidDate = null;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: Payment::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    #[ApiProperty(example: ['/api/payments/3fa85f64-5717-4562-b3fc-2c963f66afa6'])]
    private Collection $payments;

    #[ORM\OneToOne(inversedBy: 'invoice', targetEntity: Quote::class)]
    #[Groups(['invoice_api:read'])]
    #[ApiProperty(example: '/api/quotes/3fa85f64-5717-4562-b3fc-2c963f66afa6')]
    private ?Quote $quote = null;

    /**
     * @var Collection<int, Line>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: Line::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'You need to add at least 1 line to the Invoice')]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private Collection $lines;

    /**
     * @var Collection<int,InvoiceContact>
     */
    #[ApiProperty(
        writableLink: true,
        example: ['/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6/contact/3fa85f64-5717-4562-b3fc-2c963f66afa6'],
    )]
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceContact::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[Assert\Count(min: 1, minMessage: 'You need to select at least 1 user to attach to the Invoice')]
    #[Groups(['invoice_api:read', 'invoice_api:write'])]
    private Collection $users;

    public function __construct()
    {
        parent::__construct();

        $this->payments = new ArrayCollection();
        $this->lines = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->balance = BigInteger::zero();
        $this->invoiceDate = new DateTimeImmutable();
        $this->setUuid(Uuid::v7());
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return Uuid::fromString($this->uuid);
    }

    public function setUuid(Uuid $uuid): self
    {
        $this->uuid = $uuid->toString();
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getBalance(): BigNumber
    {
        return $this->balance;
    }

    /**
     * @throws MathException
     */
    public function setBalance(BigNumber | float | int | string $balance): self
    {
        $this->balance = BigNumber::of($balance);

        return $this;
    }

    public function getDue(): ?DateTimeInterface
    {
        return $this->due;
    }

    public function setDue(?DateTimeInterface $due): self
    {
        $this->due = $due;
        return $this;
    }

    public function getPaidDate(): ?DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(?DateTime $paidDate): self
    {
        $this->paidDate = $paidDate;
        return $this;
    }

    public function addLine(LineInterface $line): self
    {
        assert($line instanceof Line);
        $this->lines[] = $line;
        $line->setInvoice($this);
        if (isset($this->company)) {
            $line->setCompany($this->getCompany());
        }
        return $this;
    }

    public function removeLine(Line $line): self
    {
        $this->lines->removeElement($line);
        $line->setInvoice(null);
        return $this;
    }

    /**
     * @return Collection<int, Line>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    #[ORM\PrePersist]
    public function updateLines(): void
    {
        foreach ($this->lines as $line) {
            $line->setInvoice($this);
        }
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;
        $payment->setInvoice($this);
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

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(Quote $quote): self
    {
        $this->quote = $quote;
        $quote->setInvoice($this);
        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getUsers(): Collection
    {
        return $this->users->map(static fn (InvoiceContact $invoiceContact) => $invoiceContact->getContact());
    }

    /**
     * @param (Contact|InvoiceContact)[] $users
     */
    public function setUsers(array $users): self
    {
        $contacts = [];
        foreach ($users as $user) {
            if ($user instanceof InvoiceContact) {
                $contacts[] = $user;
            } elseif ($user instanceof Contact) {
                $invoiceContact = new InvoiceContact();
                $invoiceContact->setContact($user);
                $invoiceContact->setInvoice($this);
                $contacts[] = $invoiceContact;
            }
        }
        $this->users = new ArrayCollection($contacts);
        return $this;
    }

    public function addUser(Contact $user): self
    {
        $invoiceContact = new InvoiceContact();
        $invoiceContact->setContact($user);
        $invoiceContact->setInvoice($this);
        $this->users[] = $invoiceContact;
        return $this;
    }

    public function __clone()
    {
        $lines = $this->lines;
        $this->lines = new ArrayCollection();
        foreach ($lines as $line) {
            $this->lines->add(clone $line);
        }
        $this->setUuid(Uuid::v7());
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getInvoiceDate(): DateTimeInterface
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(DateTimeInterface $invoiceDate): self
    {
        $this->invoiceDate = $invoiceDate;

        return $this;
    }

    public function __toString(): string
    {
        return $this->invoiceId;
    }
}
