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

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Billing\BillingDocumentKind;
use SolidInvoice\CoreBundle\Repository\AmbiguousDiscountUnitRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * One row per document that SOL-342's `Version30100_13` migration left alone because its
 * stored percentage was ambiguous between the pre-fix hundredths convention and a
 * legitimate whole percent, carrying the percentage as it stood immediately before the
 * migration canonicalised the column it came from.
 *
 * Read-only: the application never writes this table, the migration does.
 */
#[ORM\Table(name: AmbiguousDiscountUnit::TABLE_NAME)]
#[ORM\Index(columns: ['company_id'])]
#[ORM\Entity(repositoryClass: AmbiguousDiscountUnitRepository::class, readOnly: true)]
class AmbiguousDiscountUnit
{
    use CompanyAware;

    final public const string TABLE_NAME = 'ambiguous_discount_units';

    /**
     * Deliberately the audited document's own id, not a minted one. These rows are
     * written by a raw `INSERT ... SELECT` inside `Version30100_13`, and there is no
     * portable way to mint a ULID in SQL across every platform this product supports.
     * Reusing the document's id costs nothing — ULIDs do not collide across the three
     * source tables — and turns a double capture into a primary-key violation instead
     * of a silently doubled count. Do not add a `CustomIdGenerator` here.
     */
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;

    #[ORM\Column(name: 'document_type', type: Types::STRING, length: 20, enumType: BillingDocumentKind::class)]
    private BillingDocumentKind $documentType;

    /**
     * The value as it stood immediately before the canonicalisation, in the same unit
     * (`FLOAT`) as the source column, so a later comparison against the document's
     * current value is an exact comparison between identical doubles.
     */
    #[ORM\Column(name: 'captured_percentage', type: Types::FLOAT)]
    private float $capturedPercentage;

    #[ORM\Column(name: 'captured_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $capturedAt;

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getDocumentType(): BillingDocumentKind
    {
        return $this->documentType;
    }

    public function getCapturedPercentage(): float
    {
        return $this->capturedPercentage;
    }

    public function getCapturedAt(): DateTimeImmutable
    {
        return $this->capturedAt;
    }
}
