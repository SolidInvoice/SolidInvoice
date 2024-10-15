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
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: RecurringInvoice::TABLE_NAME)]
#[ORM\Entity(repositoryClass: RecurringInvoiceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: [
        'groups' => ['recurring_invoice_api'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['create_recurring_invoice_api'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
class RecurringInvoice extends BaseInvoice
{
    final public const TABLE_NAME = 'recurring_invoices';
    use Archivable;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[Serialize\Groups(['recurring_invoice_api', 'client_api'])]
    private ?UuidInterface $id = null;

    #[ApiProperty(iris: ['https://schema.org/Organization'])]
    #[ORM\ManyToOne(targetEntity: Client::class, cascade: ['persist'], inversedBy: 'recurringInvoices')]
    #[Assert\NotBlank]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api'])]
    protected ?Client $client = null;

    #[ORM\Column(name: 'frequency', type: Types::STRING, nullable: true)]
    #[Serialize\Groups(['recurring_invoice_api', 'client_api', 'create_recurring_invoice_api'])]
    private ?string $frequency = null;

    #[ORM\Column(name: 'date_start', type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank(groups: ['Recurring'])]
    #[Assert\Date(groups: ['Recurring'])]
    #[Serialize\Groups(['recurring_invoice_api', 'client_api', 'create_recurring_invoice_api'])]
    private ?DateTimeInterface $dateStart = null;

    #[ORM\Column(name: 'date_end', type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Serialize\Groups(['recurring_invoice_api', 'client_api', 'create_recurring_invoice_api'])]
    private ?DateTimeInterface $dateEnd = null;

    /**
     * @var Collection<int, Line>
     */
    #[ORM\OneToMany(mappedBy: 'recurringInvoice', targetEntity: Line::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'You need to add at least 1 item to the Invoice')]
    #[Serialize\Groups(['recurring_invoice_api', 'client_api', 'create_recurring_invoice_api'])]
    protected Collection $items;

    /**
     * @var Collection<int, RecurringInvoiceContact>
     */
    #[ApiProperty(writableLink: true)]
    #[ORM\OneToMany(mappedBy: 'recurringInvoice', targetEntity: RecurringInvoiceContact::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[Assert\Count(min: 1, minMessage: 'You need to select at least 1 user to attach to the Invoice')]
    #[Serialize\Groups(['invoice_api', 'recurring_invoice_api', 'client_api', 'create_invoice_api', 'create_recurring_invoice_api'])]
    protected Collection $users;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
        parent::__construct();
    }

    public function getId(): ?UuidInterface
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

    public function addItem(Line $item): self
    {
        $this->items[] = $item;
        $item->setInvoice($this);

        return $this;
    }

    public function removeItem(Line $item): self
    {
        $this->items->removeElement($item);
        $item->setInvoice(null);

        return $this;
    }

    /**
     * @return Collection<int, Line>
     */
    public function getItems(): Collection
    {
        return $this->items;
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
