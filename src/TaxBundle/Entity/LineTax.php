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

namespace SolidInvoice\TaxBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Repository\LineTaxRepository;
use SolidInvoice\TaxBundle\Validator\Constraints\ExactlyOneLine;
use SolidInvoice\TaxBundle\Validator\Constraints\IncompatibleTaxConfiguration;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see \SolidInvoice\TaxBundle\Tests\Entity\LineTaxTest
 */
#[ORM\Table(name: LineTax::TABLE_NAME)]
#[ORM\Entity(repositoryClass: LineTaxRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ExactlyOneLine]
#[IncompatibleTaxConfiguration]
class LineTax
{
    final public const string TABLE_NAME = 'line_tax';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: Tax::class)]
    #[ORM\JoinColumn(name: 'tax_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write', 'quote_api:read', 'quote_api:write'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private ?Tax $tax = null;

    #[ORM\ManyToOne(targetEntity: InvoiceLine::class, inversedBy: 'taxes')]
    #[ORM\JoinColumn(name: 'invoice_line_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?InvoiceLine $invoiceLine = null;

    #[ORM\ManyToOne(targetEntity: QuoteLine::class, inversedBy: 'taxes')]
    #[ORM\JoinColumn(name: 'quote_line_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?QuoteLine $quoteLine = null;

    #[ORM\Column(name: 'name_snapshot', type: Types::STRING, length: 32)]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read', 'quote_api:read'])]
    private ?string $nameSnapshot = null;

    #[ORM\Column(name: 'rate_snapshot', type: Types::DECIMAL, precision: 10, scale: 4)]
    #[Assert\NotBlank]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read', 'quote_api:read'])]
    private string $rateSnapshot = '0.0000';

    #[ORM\Column(name: 'category_snapshot', type: Types::STRING, length: 32, enumType: TaxCategory::class, options: ['default' => TaxCategory::Standard->value])]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read', 'quote_api:read'])]
    private TaxCategory $categorySnapshot = TaxCategory::Standard;

    #[ORM\Column(name: 'type_snapshot', type: Types::STRING, length: 32, enumType: TaxType::class)]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read', 'quote_api:read'])]
    private TaxType $typeSnapshot = TaxType::Exclusive;

    #[ORM\Column(name: 'compound', type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write', 'quote_api:read', 'quote_api:write'])]
    private bool $compound = false;

    #[ORM\Column(name: 'sequence', type: Types::SMALLINT, options: ['default' => 0])]
    #[Groups(['invoice_api:read', 'invoice_api:write', 'recurring_invoice_api:read', 'recurring_invoice_api:write', 'quote_api:read', 'quote_api:write'])]
    private int $sequence = 0;

    #[ORM\Column(name: 'amount', type: BigIntegerType::NAME)]
    #[Groups(['invoice_api:read', 'recurring_invoice_api:read', 'quote_api:read'])]
    private BigNumber $amount;

    #[ORM\Column(name: 'snapshotted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeInterface $snapshottedAt = null;

    public function __construct()
    {
        $this->amount = BigInteger::zero();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    public function getInvoiceLine(): ?InvoiceLine
    {
        return $this->invoiceLine;
    }

    public function setInvoiceLine(?InvoiceLine $invoiceLine): self
    {
        $this->invoiceLine = $invoiceLine;

        return $this;
    }

    public function getQuoteLine(): ?QuoteLine
    {
        return $this->quoteLine;
    }

    public function setQuoteLine(?QuoteLine $quoteLine): self
    {
        $this->quoteLine = $quoteLine;

        return $this;
    }

    public function getNameSnapshot(): ?string
    {
        return $this->nameSnapshot;
    }

    public function setNameSnapshot(string $nameSnapshot): self
    {
        $this->nameSnapshot = $nameSnapshot;

        return $this;
    }

    public function getRateSnapshot(): string
    {
        return $this->rateSnapshot;
    }

    /**
     * @throws MathException
     */
    public function setRateSnapshot(BigNumber|string|float|int $rateSnapshot): self
    {
        $normalised = is_float($rateSnapshot) ? (string) $rateSnapshot : $rateSnapshot;
        $this->rateSnapshot = BigDecimal::of($normalised)->toScale(4)->__toString();

        return $this;
    }

    public function getCategorySnapshot(): TaxCategory
    {
        return $this->categorySnapshot;
    }

    public function setCategorySnapshot(TaxCategory $categorySnapshot): self
    {
        $this->categorySnapshot = $categorySnapshot;

        return $this;
    }

    public function getTypeSnapshot(): TaxType
    {
        return $this->typeSnapshot;
    }

    public function setTypeSnapshot(TaxType $typeSnapshot): self
    {
        $this->typeSnapshot = $typeSnapshot;

        return $this;
    }

    public function isCompound(): bool
    {
        return $this->compound;
    }

    public function setCompound(bool $compound): self
    {
        $this->compound = $compound;

        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getAmount(): BigNumber
    {
        return $this->amount;
    }

    /**
     * @throws MathException
     */
    public function setAmount(BigNumber|int|string|float $amount): self
    {
        $this->amount = BigNumber::of(is_float($amount) ? (string) $amount : $amount);

        return $this;
    }

    public function getSnapshottedAt(): ?DateTimeInterface
    {
        return $this->snapshottedAt;
    }

    public function setSnapshottedAt(?DateTimeInterface $snapshottedAt): self
    {
        $this->snapshottedAt = $snapshottedAt;

        return $this;
    }

    /**
     * Populate snapshot fields from a source Tax entity.
     *
     * Refuses to overwrite once {@see $snapshottedAt} has been set — that timestamp is
     * the canonical "frozen" marker, written by
     * {@see \SolidInvoice\TaxBundle\Listener\SnapshotTaxesOnIssueListener} on the
     * draft→issued transition.
     *
     * @throws MathException
     */
    public function snapshotFrom(Tax $tax): self
    {
        if ($this->snapshottedAt instanceof DateTimeInterface) {
            return $this;
        }

        $this->tax = $tax;
        $this->nameSnapshot = (string) $tax->getName();
        $this->setRateSnapshot((string) ($tax->getRate() ?? 0));
        $this->categorySnapshot = $tax->getCategory();
        $this->typeSnapshot = TaxType::from((string) ($tax->getType() ?? TaxType::Exclusive->value));
        $this->compound = $tax->isCompound();

        return $this;
    }

    /**
     * Populate snapshot fields from the linked Tax on persist when callers (REST API)
     * skip the explicit snapshotFrom() step. Form-bound flows trigger snapshotFrom() in
     * LineTaxType::POST_SUBMIT; this is the safety net for everything else.
     */
    #[ORM\PrePersist]
    public function autoSnapshotOnPersist(): void
    {
        if ($this->nameSnapshot !== null && $this->nameSnapshot !== '') {
            return;
        }

        if ($this->tax instanceof Tax) {
            $this->snapshotFrom($this->tax);
        }
    }

    /**
     * Auto-inherit company from the parent line when not already set.
     */
    #[ORM\PrePersist]
    public function inheritCompanyFromParentLine(PrePersistEventArgs $args): void
    {
        if (isset($this->company)) {
            return;
        }

        $parent = $this->invoiceLine ?? $this->quoteLine;

        if ($parent === null) {
            return;
        }

        $this->company = $parent->getCompany();
    }

    /**
     * Mark the timestamp at which the snapshot was frozen.
     */
    public function freeze(?DateTimeInterface $at = null): self
    {
        $this->snapshottedAt = $at ?? CarbonImmutable::now();

        return $this;
    }
}
