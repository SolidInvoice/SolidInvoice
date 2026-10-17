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

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * Freezes `currency_code` on pre-upgrade invoices and quotes that were already issued before
 * {@see Version30100_7} added the column, to the value they are already displaying — a
 * pre-upgrade issued document has `currency_code IS NULL` and so falls back to the client's
 * current currency in {@see \SolidInvoice\CoreBundle\Traits\Entity\HasFrozenCurrency::getCurrency()},
 * which is exactly the value this migration writes. The write is therefore a visual no-op on
 * the day of the upgrade: what changes is only that the document stops restating itself when
 * the client's currency changes later.
 *
 * `recurring_invoices` is deliberately out of scope: it is never issued, nothing freezes it on
 * the forward path, and a recurring invoice template is meant to keep following its client.
 *
 * Every row this migration touches is captured into `document_currency_backfill` with the value
 * it is about to write, before it writes it, so the write is reversible and so a row this
 * migration could not resolve is reported rather than guessed at. {@see preDown()} restores from
 * that capture and {@see down()} drops the table; neither touches the `currency_code` column
 * added by Version30100_7, which this migration only ever writes into.
 *
 * On the in-app upgrade path ({@see \SolidInvoice\InstallBundle\Installer\Database\Migration})
 * every migration is stamped applied from a schema diff rather than executed, so this backfill
 * does not run there and cannot be run afterwards once the version is stamped. It is effective
 * only via `bin/console doctrine:migrations:migrate`, until SOL-173 lands.
 *
 * @see \SolidInvoice\CoreBundle\Entity\DocumentCurrencyBackfill
 * @see \SolidInvoice\CoreBundle\Tests\Migration\DocumentCurrencyBackfillMigrationTest
 */
final class Version30100_21 extends AbstractMigration
{
    private const string TABLE_NAME = 'document_currency_backfill';

    private const string INVOICE_ISSUED_EVIDENCE = <<<'SQL'
        (i.status IN ('pending', 'overdue', 'paid')
            OR (i.status IN ('cancelled', 'archived')
                AND (i.paid_date IS NOT NULL
                    OR EXISTS (SELECT 1 FROM payments ev WHERE ev.invoice_id = i.id AND ev.company_id = i.company_id))))
        SQL;

    private const string INVOICE_CANCELLED_WITHOUT_EVIDENCE = <<<'SQL'
        (i.status IN ('cancelled', 'archived')
            AND i.paid_date IS NULL
            AND NOT EXISTS (SELECT 1 FROM payments ev WHERE ev.invoice_id = i.id AND ev.company_id = i.company_id))
        SQL;

    private const string QUOTE_ISSUED_EVIDENCE = <<<'SQL'
        (q.status IN ('pending', 'accepted', 'declined')
            OR (q.status IN ('cancelled', 'archived')
                AND EXISTS (SELECT 1 FROM invoices ev WHERE ev.quote_id = q.id AND ev.company_id = q.company_id)))
        SQL;

    private const string QUOTE_CANCELLED_WITHOUT_EVIDENCE = <<<'SQL'
        (q.status IN ('cancelled', 'archived')
            AND NOT EXISTS (SELECT 1 FROM invoices ev WHERE ev.quote_id = q.id AND ev.company_id = q.company_id))
        SQL;

    public function getDescription(): string
    {
        return 'Backfill the frozen currency_code onto pre-upgrade issued invoices and quotes, with a reversible capture of every row touched';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('document_type', Types::STRING, ['length' => 20, 'notnull' => true]);
        $table->addColumn('document_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('currency_code', Types::STRING, ['length' => 3, 'notnull' => false]);
        $table->addColumn('source', Types::STRING, ['length' => 20, 'notnull' => false]);
        $table->addColumn('outcome', Types::STRING, ['length' => 32, 'notnull' => true]);
        $table->addColumn('captured_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->setPrimaryKey(['document_type', 'document_id']);
        $table->addIndex(['company_id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $capturedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->backfillInvoices($capturedAt);
        $this->backfillQuotes($capturedAt);
    }

    public function preDown(Schema $schema): void
    {
        // The live database, not the $schema parameter: Doctrine Migrations' own test harness
        // exercises preDown() with an empty Schema() that never reflects what up() created.
        if (! $this->connection->createSchemaManager()->tablesExist([self::TABLE_NAME])) {
            // An installer-stamped install never ran postUp(), so there is nothing to restore.
            return;
        }

        foreach (['invoice' => 'invoices', 'quote' => 'quotes'] as $documentType => $table) {
            $this->connection->executeStatement(
                <<<SQL
                    UPDATE {$table}
                       SET currency_code = NULL
                     WHERE EXISTS (
                        SELECT 1
                          FROM document_currency_backfill b
                         WHERE b.document_type = :documentType
                           AND b.document_id = {$table}.id
                           AND b.outcome = 'frozen'
                           AND b.currency_code = {$table}.currency_code
                     )
                    SQL
                ,
                ['documentType' => $documentType],
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE_NAME)) {
            $schema->dropTable(self::TABLE_NAME);
        }
    }

    /**
     * @throws Exception
     */
    private function backfillInvoices(string $capturedAt): void
    {
        // Heredocs only interpolate variables, never self::CONST, hence the locals.
        $issuedEvidence = self::INVOICE_ISSUED_EVIDENCE;
        $cancelledWithoutEvidence = self::INVOICE_CANCELLED_WITHOUT_EVIDENCE;

        // 1. Every payment on the invoice agrees on a non-blank currency: that currency wins.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'invoice', i.id, i.company_id, pay.single_currency, 'payment', 'frozen', :capturedAt
                  FROM invoices i
                  INNER JOIN (
                        SELECT p.invoice_id,
                               COUNT(DISTINCT p.currency_code) AS currency_count,
                               SUM(CASE WHEN p.currency_code IS NULL OR TRIM(p.currency_code) = '' THEN 1 ELSE 0 END) AS blank_count,
                               MIN(p.currency_code) AS single_currency
                          FROM payments p
                          INNER JOIN invoices pi ON pi.id = p.invoice_id AND pi.company_id = p.company_id
                         GROUP BY p.invoice_id
                  ) pay ON pay.invoice_id = i.id
                 WHERE i.currency_code IS NULL
                   AND {$issuedEvidence}
                   AND pay.currency_count = 1
                   AND pay.blank_count = 0
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        // 2. The invoice's payments disagree, or one of them has no resolvable currency: a data
        //    problem to surface, never a fallback to the client.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'invoice', i.id, i.company_id, NULL, NULL, 'conflicting_payments', :capturedAt
                  FROM invoices i
                  INNER JOIN (
                        SELECT p.invoice_id,
                               COUNT(DISTINCT p.currency_code) AS currency_count,
                               SUM(CASE WHEN p.currency_code IS NULL OR TRIM(p.currency_code) = '' THEN 1 ELSE 0 END) AS blank_count
                          FROM payments p
                          INNER JOIN invoices pi ON pi.id = p.invoice_id AND pi.company_id = p.company_id
                         GROUP BY p.invoice_id
                  ) pay ON pay.invoice_id = i.id
                 WHERE i.currency_code IS NULL
                   AND {$issuedEvidence}
                   AND (pay.currency_count > 1 OR pay.blank_count > 0)
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        // 3. No payments: fall back to the client's resolved currency, company setting second.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'invoice', i.id, i.company_id,
                       COALESCE(NULLIF(TRIM(c.currency), ''), NULLIF(TRIM(cfg.setting_value), '')),
                       CASE WHEN NULLIF(TRIM(c.currency), '') IS NOT NULL THEN 'client' ELSE 'company' END,
                       'frozen', :capturedAt
                  FROM invoices i
                  LEFT JOIN clients c ON c.id = i.client_id AND c.company_id = i.company_id
                  LEFT JOIN app_config cfg ON cfg.company_id = i.company_id
                                           AND cfg.setting_key = 'system/company/currency'
                                           AND LENGTH(TRIM(cfg.setting_value)) = 3
                 WHERE i.currency_code IS NULL
                   AND {$issuedEvidence}
                   AND NOT EXISTS (SELECT 1 FROM payments p2 WHERE p2.invoice_id = i.id AND p2.company_id = i.company_id)
                   AND COALESCE(NULLIF(TRIM(c.currency), ''), NULLIF(TRIM(cfg.setting_value), '')) IS NOT NULL
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        // 4. No payments and neither the client nor the company resolves a currency.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'invoice', i.id, i.company_id, NULL, NULL, 'unresolvable_currency', :capturedAt
                  FROM invoices i
                  LEFT JOIN clients c ON c.id = i.client_id AND c.company_id = i.company_id
                  LEFT JOIN app_config cfg ON cfg.company_id = i.company_id
                                           AND cfg.setting_key = 'system/company/currency'
                                           AND LENGTH(TRIM(cfg.setting_value)) = 3
                 WHERE i.currency_code IS NULL
                   AND {$issuedEvidence}
                   AND NOT EXISTS (SELECT 1 FROM payments p3 WHERE p3.invoice_id = i.id AND p3.company_id = i.company_id)
                   AND COALESCE(NULLIF(TRIM(c.currency), ''), NULLIF(TRIM(cfg.setting_value), '')) IS NULL
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        // 5. Cancelled or archived with no positive evidence it was ever issued: reported, not guessed.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'invoice', i.id, i.company_id, NULL, NULL, 'indeterminate_cancelled', :capturedAt
                  FROM invoices i
                 WHERE i.currency_code IS NULL
                   AND {$cancelledWithoutEvidence}
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        $this->writeFrozenCurrency('invoice', 'invoices');
    }

    /**
     * @throws Exception
     */
    private function backfillQuotes(string $capturedAt): void
    {
        $issuedEvidence = self::QUOTE_ISSUED_EVIDENCE;
        $cancelledWithoutEvidence = self::QUOTE_CANCELLED_WITHOUT_EVIDENCE;

        // Quotes have no payment tier: straight to the client, company setting second.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'quote', q.id, q.company_id,
                       COALESCE(NULLIF(TRIM(c.currency), ''), NULLIF(TRIM(cfg.setting_value), '')),
                       CASE WHEN NULLIF(TRIM(c.currency), '') IS NOT NULL THEN 'client' ELSE 'company' END,
                       'frozen', :capturedAt
                  FROM quotes q
                  LEFT JOIN clients c ON c.id = q.client_id AND c.company_id = q.company_id
                  LEFT JOIN app_config cfg ON cfg.company_id = q.company_id
                                           AND cfg.setting_key = 'system/company/currency'
                                           AND LENGTH(TRIM(cfg.setting_value)) = 3
                 WHERE q.currency_code IS NULL
                   AND {$issuedEvidence}
                   AND COALESCE(NULLIF(TRIM(c.currency), ''), NULLIF(TRIM(cfg.setting_value), '')) IS NOT NULL
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        // Neither the client nor the company resolves a currency — covers client_id IS NULL too.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'quote', q.id, q.company_id, NULL, NULL, 'unresolvable_currency', :capturedAt
                  FROM quotes q
                  LEFT JOIN clients c ON c.id = q.client_id AND c.company_id = q.company_id
                  LEFT JOIN app_config cfg ON cfg.company_id = q.company_id
                                           AND cfg.setting_key = 'system/company/currency'
                                           AND LENGTH(TRIM(cfg.setting_value)) = 3
                 WHERE q.currency_code IS NULL
                   AND {$issuedEvidence}
                   AND COALESCE(NULLIF(TRIM(c.currency), ''), NULLIF(TRIM(cfg.setting_value), '')) IS NULL
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        // Cancelled or archived with no derived invoice to prove it ever reached accepted.
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO document_currency_backfill (document_type, document_id, company_id, currency_code, source, outcome, captured_at)
                SELECT 'quote', q.id, q.company_id, NULL, NULL, 'indeterminate_cancelled', :capturedAt
                  FROM quotes q
                 WHERE q.currency_code IS NULL
                   AND {$cancelledWithoutEvidence}
                SQL
            ,
            ['capturedAt' => $capturedAt],
        );

        $this->writeFrozenCurrency('quote', 'quotes');
    }

    /**
     * The only write to money: reads the capture, never the source data, so the two cannot
     * diverge and the resolution logic exists once.
     *
     * @throws Exception
     */
    private function writeFrozenCurrency(string $documentType, string $table): void
    {
        $this->connection->executeStatement(
            <<<SQL
                UPDATE {$table}
                   SET currency_code = (
                        SELECT b.currency_code
                          FROM document_currency_backfill b
                         WHERE b.document_type = :documentType
                           AND b.document_id = {$table}.id
                           AND b.outcome = 'frozen'
                   )
                 WHERE currency_code IS NULL
                   AND EXISTS (
                        SELECT 1
                          FROM document_currency_backfill b2
                         WHERE b2.document_type = :documentType
                           AND b2.document_id = {$table}.id
                           AND b2.outcome = 'frozen'
                   )
                SQL
            ,
            ['documentType' => $documentType],
        );
    }
}
