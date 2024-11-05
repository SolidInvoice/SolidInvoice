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

namespace SolidInvoice\QuoteBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use SolidInvoice\QuoteBundle\Traits\QuoteStatusTrait;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: Quote::TABLE_NAME)]
#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [new GetCollection(), new Get(), new Post(), new Patch(), new Delete()],
    normalizationContext: [
        'groups' => ['quote_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['quote_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/quotes',
    operations: [new GetCollection()],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'quotes',
            fromClass: Client::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['quote_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['quote_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
class Quote
{
    final public const TABLE_NAME = 'quotes';
    use Archivable;
    use QuoteStatusTrait {
        Archivable::isArchived insteadof QuoteStatusTrait;
    }
    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['quote_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'quote_id', type: Types::STRING, length: 255)]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private string $quoteId = '';

    #[ORM\Column(name: 'uuid', type: Types::STRING, length: 36)]
    #[Groups(['quote_api:read'])]
    #[ApiProperty(writable: false)]
    private ?string $uuid = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 25)]
    #[Groups(['quote_api:read'])]
    #[ApiProperty(writable: false)]
    private ?string $status = null;

    #[ApiProperty(
        example: '/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6',
        iris: ['https://schema.org/Organization']
    )]
    #[ORM\ManyToOne(targetEntity: Client::class, cascade: ['persist'], inversedBy: 'quotes')]
    #[Assert\NotBlank]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private ?Client $client = null;

    #[ORM\Column(name: 'total_amount', type: BigIntegerType::NAME)]
    #[Groups(['quote_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    private BigNumber $total;

    #[ORM\Column(name: 'baseTotal_amount', type: BigIntegerType::NAME)]
    #[Groups(['quote_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    private BigNumber $baseTotal;

    #[ORM\Column(name: 'tax_amount', type: BigIntegerType::NAME)]
    #[Groups(['quote_api:read'])]
    #[ApiProperty(
        writable: false,
        openapiContext: [
            'type' => 'number',
        ],
        jsonSchemaContext: [
            'type' => 'number',
        ]
    )]
    private BigNumber $tax;

    #[ORM\Embedded(class: Discount::class)]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'oneOf' => [
                        ['type' => 'string', 'enum' => ['percentage', 'money']],
                        ['type' => 'null'],
                    ],
                ],
                'value' => [
                    'oneOf' => [
                        ['type' => 'number'],
                        ['type' => 'null'],
                    ],
                ],
            ],
        ],
        jsonSchemaContext: [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'oneOf' => [
                        ['type' => 'string', 'enum' => ['percentage', 'money']],
                        ['type' => 'null'],
                    ],
                ],
                'value' => [
                    'oneOf' => [
                        ['type' => 'number'],
                        ['type' => 'null'],
                    ],
                ],
            ],
        ]
    )]
    private Discount $discount;

    #[ORM\Column(name: 'terms', type: Types::TEXT, nullable: true)]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private ?string $terms = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private ?string $notes = null;

    #[ORM\Column(name: 'due', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(type: DateTimeInterface::class)]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private ?DateTimeInterface $due = null;

    /**
     * @var Collection<int, Line>
     */
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: Line::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'You need to add at least 1 line to the Quote')]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private Collection $lines;

    /**
     * @var Collection<int,QuoteContact>
     */
    #[ApiProperty(
        writableLink: true,
        example: ['/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6/contact/3fa85f64-5717-4562-b3fc-2c963f66afa6'],
    )]
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteContact::class, cascade: ['persist', 'remove'])]
    #[Assert\Count(min: 1, minMessage: 'You need to select at least 1 user to attach to the Quote')]
    #[Groups(['quote_api:read', 'quote_api:write'])]
    private Collection $users;

    #[ORM\OneToOne(mappedBy: 'quote', targetEntity: Invoice::class)]
    #[Groups(['quote_api:read'])]
    #[ApiProperty(
        example: '/api/invoices/3fa85f64-5717-4562-b3fc-2c963f66afa6',
    )]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        $this->discount = new Discount();
        $this->lines = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->baseTotal = BigDecimal::zero();
        $this->tax = BigDecimal::zero();
        $this->total = BigDecimal::zero();
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

    /**
     * @return Collection<int, Contact>
     */
    public function getUsers(): Collection
    {
        return $this->users->map(static fn (QuoteContact $contact): Contact => $contact->getContact());
    }

    /**
     * @param iterable<Contact|QuoteContact> $users
     */
    public function setUsers(iterable $users): self
    {
        $contacts = [];
        foreach ($users as $user) {
            if ($user instanceof QuoteContact) {
                $contacts[] = $user;
            } elseif ($user instanceof Contact) {
                $quoteContact = new QuoteContact();
                $quoteContact->setContact($user);
                $quoteContact->setQuote($this);
                $contacts[] = $quoteContact;
            }
        }
        $this->users = new ArrayCollection($contacts);
        return $this;
    }

    public function addUser(Contact $user): self
    {
        $quoteContact = new QuoteContact();
        $quoteContact->setContact($user);
        $quoteContact->setQuote($this);
        $this->users[] = $quoteContact;
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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getTotal(): BigNumber
    {
        return $this->total;
    }

    /**
     * @throws MathException
     */
    public function setTotal(BigNumber|float|int|string $total): self
    {
        $this->total = BigNumber::of($total);

        return $this;
    }

    public function getBaseTotal(): BigNumber
    {
        return $this->baseTotal;
    }

    /**
     * @throws MathException
     */
    public function setBaseTotal(BigNumber|float|int|string $baseTotal): self
    {
        $this->baseTotal = BigNumber::of($baseTotal);

        return $this;
    }

    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    public function setDiscount(Discount $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function getDue(): ?DateTimeInterface
    {
        return $this->due;
    }

    public function setDue(DateTimeInterface $due): self
    {
        $this->due = $due;
        return $this;
    }

    public function addLine(LineInterface $line): self
    {
        assert($line instanceof Line);
        $this->lines[] = $line;
        $line->setQuote($this);
        return $this;
    }

    public function removeLine(Line $line): self
    {
        $this->lines->removeElement($line);
        $line->setQuote();
        return $this;
    }

    /**
     * @return Collection<int, Line>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): self
    {
        $this->terms = $terms;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getTax(): BigNumber
    {
        return $this->tax;
    }

    /**
     * @throws MathException
     */
    public function setTax(BigNumber|float|int|string $tax): self
    {
        $this->tax = BigNumber::of($tax);

        return $this;
    }

    #[ORM\PrePersist]
    public function updateLines(): void
    {
        foreach ($this->lines as $line) {
            $line->setQuote($this);
        }
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function getQuoteId(): string
    {
        return $this->quoteId;
    }

    public function setQuoteId(string $quoteId): void
    {
        $this->quoteId = $quoteId;
    }

    public function setId(Ulid $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }
}
