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
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: RecurringInvoice::TABLE_NAME)]
#[ORM\Entity(repositoryClass: RecurringInvoiceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [new GetCollection(), new Get(), new Post(), new Patch(), new Delete()],
    normalizationContext: [
        'groups' => ['recurring_invoice_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['recurring_invoice_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/recurring-invoices',
    operations: [new GetCollection()],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'recurringInvoices',
            fromClass: Client::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['recurring_invoice_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['recurring_invoice_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
class RecurringInvoice extends BaseInvoice
{
    final public const TABLE_NAME = 'recurring_invoices';
    use Archivable;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Serialize\Groups(['recurring_invoice_api:read'])]
    private ?Ulid $id = null;

    #[ApiProperty(
        example: '/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6',
        iris: ['https://schema.org/Organization']
    )]
    #[ORM\ManyToOne(targetEntity: Client::class, cascade: ['persist'], inversedBy: 'recurringInvoices')]
    #[Assert\NotBlank]
    #[Serialize\Groups(['recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected ?Client $client = null;

    #[ORM\Column(name: 'frequency', type: Types::STRING, nullable: true)]
    #[Serialize\Groups(['recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    private ?string $frequency = null;

    #[ORM\Column(name: 'date_start', type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank(groups: ['Recurring'])]
    #[Assert\Date(groups: ['Recurring'])]
    #[Serialize\Groups(['recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    private ?DateTimeInterface $dateStart = null;

    #[ORM\Column(name: 'date_end', type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Serialize\Groups(['recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    private ?DateTimeInterface $dateEnd = null;

    /**
     * @var Collection<int, RecurringInvoiceLine>
     */
    #[ORM\OneToMany(mappedBy: 'recurringInvoice', targetEntity: RecurringInvoiceLine::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'You need to add at least 1 line to the Invoice')]
    #[Serialize\Groups(['recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected Collection $lines;

    /**
     * @var Collection<int, RecurringInvoiceContact>
     */
    #[ApiProperty(
        writableLink: true,
        example: ['/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6/contact/3fa85f64-5717-4562-b3fc-2c963f66afa6'],
    )]
    #[ORM\OneToMany(mappedBy: 'recurringInvoice', targetEntity: RecurringInvoiceContact::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[Assert\Count(min: 1, minMessage: 'You need to select at least 1 user to attach to the Invoice')]
    #[Serialize\Groups(['recurring_invoice_api:read', 'recurring_invoice_api:write'])]
    protected Collection $users;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->users = new ArrayCollection();
        parent::__construct();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
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

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getDateStart(): ?DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(DateTimeInterface $dateStart = null): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(DateTimeInterface $dateEnd = null): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function addLine(RecurringInvoiceLine $line): self
    {
        $this->lines[] = $line;
        $line->setRecurringInvoice($this);

        return $this;
    }

    public function removeLine(RecurringInvoiceLine $line): self
    {
        $this->lines->removeElement($line);
        $line->setRecurringInvoice(null);

        return $this;
    }

    /**
     * @return Collection<int, RecurringInvoiceLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getUsers(): Collection
    {
        return $this->users->map(static fn (RecurringInvoiceContact $user): Contact => $user->getContact());
    }

    /**
     * @param (RecurringInvoiceContact|Contact)[] $users
     */
    public function setUsers(array $users): self
    {
        $contacts = [];

        foreach ($users as $user) {
            if ($user instanceof Contact) {
                $recurringInvoiceContact = new RecurringInvoiceContact();
                $recurringInvoiceContact->setContact($user);
                $recurringInvoiceContact->setRecurringInvoice($this);

                $contacts[] = $recurringInvoiceContact;
            } elseif ($user instanceof RecurringInvoiceContact) {
                $contacts[] = $user;
            }
        }

        $this->users = new ArrayCollection($contacts);

        return $this;
    }

    public function addUser(Contact $user): self
    {
        $recurringInvoiceContact = new RecurringInvoiceContact();
        $recurringInvoiceContact->setContact($user);
        $recurringInvoiceContact->setRecurringInvoice($this);

        $this->users[] = $recurringInvoiceContact;

        return $this;
    }
}
