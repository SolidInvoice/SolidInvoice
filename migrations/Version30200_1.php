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

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * Adds `einvoice_document`: the e-invoice transmission lifecycle and audit trail — `design` and
 * `schema` documents on SOL-89. Purely additive: no existing table is touched, no data is moved or
 * backfilled, and `down()` is a genuine inverse (see `schema` §4 on SOL-89).
 */
final class Version30200_1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the einvoice_document table: e-invoice transmission lifecycle and audit trail';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(EInvoiceDocument::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(EInvoiceDocument::TABLE_NAME);

        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME);
        $table->addColumn('invoice_id', UlidType::NAME, ['notnull' => false]);
        $table->addColumn('credit_note_id', UlidType::NAME, ['notnull' => false]);
        $table->addColumn('source_number', Types::STRING, ['length' => 255]);
        $table->addColumn('channel', Types::STRING, ['length' => 50]);
        $table->addColumn('profile', Types::STRING, ['length' => 100]);
        $table->addColumn('status', Types::STRING, ['length' => 25]);
        $table->addColumn('payload', Types::TEXT);
        $table->addColumn('payload_hash', Types::STRING, ['length' => 64]);
        $table->addColumn('payload_format', Types::STRING, ['length' => 10]);
        $table->addColumn('payload_media_type', Types::STRING, ['length' => 100]);
        $table->addColumn('payload_filename', Types::STRING, ['length' => 255]);
        $table->addColumn('external_id', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('receipt', Types::TEXT, ['notnull' => false]);
        $table->addColumn('error_code', Types::STRING, ['length' => 100, 'notnull' => false]);
        $table->addColumn('error_message', Types::TEXT, ['notnull' => false]);
        $table->addColumn('transmitted_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('completed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        // Written by the polling scan (#2679); lands here unused — `design` §8.3 on SOL-89.
        $table->addColumn('poll_after', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE);

        $table->setPrimaryKey(['id']);
        // Single-column, FK-backing — required, not the composite `company_id`-led index ADR
        // 0001 §12 prohibits. PostgreSQL does not auto-index foreign-key columns; see ADR 0001
        // Amendments §4.
        $table->addIndex(['company_id']);
        $table->addIndex(['invoice_id']);
        $table->addIndex(['credit_note_id']);
        $table->addIndex(['status', 'poll_after']);
        $table->addIndex(['external_id']);

        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint(Invoice::TABLE_NAME, ['invoice_id'], ['id'], ['onDelete' => 'SET NULL']);
        $table->addForeignKeyConstraint(CreditNote::TABLE_NAME, ['credit_note_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(EInvoiceDocument::TABLE_NAME)) {
            $schema->dropTable(EInvoiceDocument::TABLE_NAME);
        }
    }
}
