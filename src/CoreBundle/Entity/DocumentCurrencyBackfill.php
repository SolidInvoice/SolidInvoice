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
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * One row per invoice or quote {@see \DoctrineMigrations\Version30100_21} touched: the value it
 * wrote (or would have written) and why. The migration is the only writer; the application never
 * inserts, updates or deletes a row here.
 *
 * Mapped, rather than a bare table, because {@see \SolidInvoice\InstallBundle\Installer\Database\Migration}
 * builds a fresh install's schema from ORM metadata and drops any table that metadata does not
 * describe — an unmapped capture table would not survive the next in-app upgrade.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Migration\DocumentCurrencyBackfillColumnsMatchMappingTest
 */
#[ORM\Table(name: DocumentCurrencyBackfill::TABLE_NAME)]
#[ORM\Index(columns: ['company_id'])]
#[ORM\Entity(readOnly: true)]
class DocumentCurrencyBackfill
{
    use CompanyAware;

    final public const string TABLE_NAME = 'document_currency_backfill';

    #[ORM\Id]
    #[ORM\Column(name: 'document_type', type: Types::STRING, length: 20)]
    private string $documentType;

    #[ORM\Id]
    #[ORM\Column(name: 'document_id', type: UlidType::NAME)]
    private Ulid $documentId;

    #[ORM\Column(name: 'currency_code', type: Types::STRING, length: 3, nullable: true)]
    private ?string $currencyCode = null;

    #[ORM\Column(name: 'source', type: Types::STRING, length: 20, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(name: 'outcome', type: Types::STRING, length: 32)]
    private string $outcome;

    #[ORM\Column(name: 'captured_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $capturedAt;

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getDocumentId(): Ulid
    {
        return $this->documentId;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getOutcome(): string
    {
        return $this->outcome;
    }

    public function getCapturedAt(): DateTimeImmutable
    {
        return $this->capturedAt;
    }
}
