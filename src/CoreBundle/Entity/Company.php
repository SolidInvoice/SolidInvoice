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

namespace SolidInvoice\CoreBundle\Entity;

use const PHP_URL_HOST;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\AdditionalContactDetail;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoieLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Entity\UserNotification;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use function function_exists;
use function idn_to_ascii;
use function is_string;
use function parse_url;
use function preg_replace;
use function rtrim;
use function str_contains;
use function strtolower;
use function trim;

#[ORM\Table(name: Company::TABLE_NAME)]
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[UniqueEntity(fields: ['customDomain'], ignoreNull: true)]
class Company implements Stringable, SubscribableInterface
{
    final public const TABLE_NAME = 'companies';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank()]
    private string $name;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'companies')]
    private Collection $users;

    #[Assert\NotBlank()]
    public ?string $currency = '';

    #[ORM\Column(name: 'custom_domain', type: Types::STRING, length: 253, unique: true, nullable: true)]
    #[Assert\Length(max: 253)]
    #[Assert\Hostname(requireTld: true)]
    private ?string $customDomain = null;

    // Related entities: Only added here to enable orphan removal
    /**
     * @var Collection<int, ApiTokenHistory>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: ApiTokenHistory::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $apiTokenHistories;

    /**
     * @var Collection<int, Tax>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Tax::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $taxes;

    /**
     * @var Collection<int, AdditionalContactDetail>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: AdditionalContactDetail::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $additionalContactDetails;

    /**
     * @var Collection<int, Address>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Address::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $addresses;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Client::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $clients;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Contact::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $contacts;

    /**
     * @var Collection<int, ContactType>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: ContactType::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $contactTypes;

    /**
     * @var Collection<int, Credit>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Credit::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $credit;

    /**
     * @var Collection<int, UserInvitation>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: UserInvitation::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $userInvitations;

    /**
     * @var Collection<int, ApiToken>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: ApiToken::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $apiTokens;

    /**
     * @var Collection<int, Setting>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Setting::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $settings;

    /**
     * @var Collection<int, Quote>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Quote::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $quotes;

    /**
     * @var Collection<int, QuoteLine>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: QuoteLine::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $quoteLines;

    /**
     * @var Collection<int, PaymentMethod>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: PaymentMethod::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $paymentMethods;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Payment::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $payments;

    /**
     * @var Collection<int, UserNotification>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: UserNotification::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $userNotifications;

    /**
     * @var Collection<int, TransportSetting>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: TransportSetting::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $transportSettings;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Invoice::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $invoices;

    /**
     * @var Collection<int, RecurringInvoice>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: RecurringInvoice::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $recurringInvoices;

    /**
     * @var Collection<int, InvoieLine>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: InvoieLine::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $invoiceLines;

    /**
     * @var Collection<int, InvoiceReminder>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: InvoiceReminder::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $invoiceReminders;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->invoiceReminders = new ArrayCollection();
        $this->id = new Ulid();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (! $this->users->contains($user)) {
            $this->users[] = $user;
            $user->addCompany($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeCompany($this);
        }

        return $this;
    }

    public function getCustomDomain(): ?string
    {
        return $this->customDomain;
    }

    public function setCustomDomain(?string $customDomain): self
    {
        $this->customDomain = self::normalizeCustomDomain($customDomain);

        return $this;
    }

    public static function normalizeCustomDomain(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '://')) {
            $host = parse_url($value, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $value = $host;
            }
        }

        // strip path / query / fragment if any leaked in
        $value = preg_replace('~[/?#].*$~', '', $value) ?? $value;
        $value = preg_replace('~:\d+$~', '', $value) ?? $value;
        $value = rtrim($value, '.');
        $value = strtolower($value);

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($value);
            if (is_string($ascii) && $ascii !== '') {
                $value = $ascii;
            }
        }

        return $value === '' ? null : $value;
    }
}
