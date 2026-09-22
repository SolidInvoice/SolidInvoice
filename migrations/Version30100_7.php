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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use Symfony\Bridge\Doctrine\Types\UlidType;
use function array_map;
use function count;
use function implode;
use function in_array;
use function sprintf;

final class Version30100_7 extends AbstractMigration
{
    private const string INVOICE_TAX_CHECK_CONSTRAINT = 'invoice_tax_exactly_one_document';

    public function getDescription(): string
    {
        return 'Add credit notes: credit_notes table, plus nullable credit_note_id owner columns on invoice_lines and invoice_tax';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if (! $schema->hasTable(CreditNote::TABLE_NAME)) {
            $table = $schema->createTable(CreditNote::TABLE_NAME);

            $table->addColumn('id', UlidType::NAME);
            $table->addColumn('company_id', UlidType::NAME);
            $table->addColumn('client_id', UlidType::NAME);
            $table->addColumn('invoice_id', UlidType::NAME, ['notnull' => false]);
            $table->addColumn('credit_note_id', Types::STRING, ['length' => 255]);
            $table->addColumn('uuid', Types::STRING, ['length' => 36]);
            $table->addColumn('status', Types::STRING, ['length' => 25]);
            $table->addColumn('credit_note_date', Types::DATE_IMMUTABLE);
            $table->addColumn('reason', Types::TEXT, ['notnull' => false]);
            $table->addColumn('total_amount', BigIntegerType::NAME);
            $table->addColumn('baseTotal_amount', BigIntegerType::NAME);
            $table->addColumn('tax_amount', BigIntegerType::NAME);
            $table->addColumn('withholding_amount', BigIntegerType::NAME, ['notnull' => true, 'default' => 0]);
            $table->addColumn('payable_amount', BigIntegerType::NAME, ['notnull' => true, 'default' => 0]);
            $table->addColumn('discount_type', Types::STRING, ['length' => 255, 'notnull' => false]);
            $table->addColumn('discount_value_percentage', Types::FLOAT, ['notnull' => false]);
            // Discount::$valueMoney (Entity/Discount.php) is not nullable and carries no
            // default, unlike its sibling columns in this embeddable — matches that exactly.
            $table->addColumn('discount_valueMoney_amount', BigIntegerType::NAME, ['notnull' => true]);
            $table->addColumn('terms', Types::TEXT, ['notnull' => false]);
            $table->addColumn('notes', Types::TEXT, ['notnull' => false]);
            $table->addColumn('created', Types::DATETIME_IMMUTABLE);
            $table->addColumn('updated', Types::DATETIME_IMMUTABLE);
            $table->addColumn('archived', Types::BOOLEAN, ['notnull' => false]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['company_id', 'status']);
            $table->addIndex(['client_id']);
            $table->addIndex(['invoice_id']);
            $table->addUniqueIndex(['company_id', 'credit_note_id']);

            $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
            $table->addForeignKeyConstraint('clients', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);
            $table->addForeignKeyConstraint(Invoice::TABLE_NAME, ['invoice_id'], ['id'], ['onDelete' => 'SET NULL']);
        }

        $invoiceLines = $schema->getTable(InvoiceLine::TABLE_NAME);
        if (! $invoiceLines->hasColumn('credit_note_id')) {
            $invoiceLines->addColumn('credit_note_id', UlidType::NAME, ['notnull' => false]);
            $invoiceLines->addIndex(['credit_note_id']);
            $invoiceLines->addForeignKeyConstraint(CreditNote::TABLE_NAME, ['credit_note_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        $invoiceTax = $schema->getTable(InvoiceTax::TABLE_NAME);
        if (! $invoiceTax->hasColumn('credit_note_id')) {
            $invoiceTax->addColumn('credit_note_id', UlidType::NAME, ['notnull' => false]);
            $invoiceTax->addIndex(['credit_note_id']);
            $invoiceTax->addForeignKeyConstraint(CreditNote::TABLE_NAME, ['credit_note_id'], ['id'], ['onDelete' => 'CASCADE']);
        }
    }

    /**
     * Widens the "exactly one document" invariant on `invoice_tax` to the fourth owner. Without
     * this, a credit-note-owned tax row (only `credit_note_id` set) would violate the
     * three-column CHECK constraint {@see \DoctrineMigrations\Version30000_9} added, on every
     * platform that enforces it.
     *
     * Best-effort exactly like {@see \DoctrineMigrations\Version30000_9::addInvoiceTaxCheckConstraint()}:
     * the `ExactlyOneDocumentValidator` remains the canonical enforcement, this is defence in depth.
     *
     * Guarded on the real `credit_note_id` column existing: `postUp()` always runs for real, even
     * under `--dry-run`, where `up()`'s Schema-tool column addition never actually reaches the
     * database. Without this guard, a dry run would drop the live three-column check for real
     * and then fail to re-add the four-column one (the column it references does not exist yet),
     * silently stripping the invariant from a production database that a dry run must not touch.
     *
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        if (! $this->columnExists(InvoiceTax::TABLE_NAME, 'credit_note_id')) {
            return;
        }

        $this->replaceExactlyOneDocumentCheck('invoice_id', 'quote_id', 'recurring_invoice_id', 'credit_note_id');
    }

    /**
     * Restores the pre-credit-note three-column invariant before `down()` drops the column it
     * would otherwise still reference.
     *
     * @throws Exception
     */
    public function preDown(Schema $schema): void
    {
        $this->replaceExactlyOneDocumentCheck('invoice_id', 'quote_id', 'recurring_invoice_id');
    }

    /**
     * Drops the existing check (if it is actually there) and adds the given one in its place.
     *
     * The existence check before the `DROP` matters beyond the try/catch already wrapping it:
     * on PostgreSQL, a `DROP CONSTRAINT` that errors — the constraint being absent is exactly
     * such an error — aborts the whole transaction, and every statement after it (including the
     * migration's own version-tracking write) fails with "current transaction is aborted",
     * outside this method's try/catch. Never emitting a `DROP` we already know will fail avoids
     * that entirely, rather than merely catching the PHP-level exception it raises.
     *
     * @throws Exception
     */
    private function replaceExactlyOneDocumentCheck(string ...$columns): void
    {
        if ($this->constraintExists(InvoiceTax::TABLE_NAME, self::INVOICE_TAX_CHECK_CONSTRAINT)) {
            $this->dropCheckConstraintIfSupported(InvoiceTax::TABLE_NAME, self::INVOICE_TAX_CHECK_CONSTRAINT);
        }

        $this->addCheckConstraintIfSupported(InvoiceTax::TABLE_NAME, self::INVOICE_TAX_CHECK_CONSTRAINT, ...$columns);
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->sm->introspectSchema()->getTable($table)->hasColumn($column);
    }

    /**
     * Portable across MySQL, MariaDB and PostgreSQL. Oracle and SQL Server don't expose
     * `information_schema` the same way, so a failure here is read as "can't tell" rather than
     * "does not exist" — falling through to the existing try/catch around the `DROP` itself,
     * which is the same coverage this method's absence would leave those two platforms with.
     */
    private function constraintExists(string $table, string $constraint): bool
    {
        if (! $this->platformSupportsCheckConstraints()) {
            return false;
        }

        try {
            return (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_name = ? AND constraint_name = ?',
                [$table, $constraint],
            ) > 0;
        } catch (Exception) {
            return true;
        }
    }

    public function down(Schema $schema): void
    {
        $invoiceTax = $schema->getTable(InvoiceTax::TABLE_NAME);
        if ($invoiceTax->hasColumn('credit_note_id')) {
            $this->dropForeignKeysOnColumn($invoiceTax, 'credit_note_id');
            $invoiceTax->dropColumn('credit_note_id');
        }

        $invoiceLines = $schema->getTable(InvoiceLine::TABLE_NAME);
        if ($invoiceLines->hasColumn('credit_note_id')) {
            $this->dropForeignKeysOnColumn($invoiceLines, 'credit_note_id');
            $invoiceLines->dropColumn('credit_note_id');
        }

        if ($schema->hasTable(CreditNote::TABLE_NAME)) {
            $schema->dropTable(CreditNote::TABLE_NAME);
        }
    }

    private function dropForeignKeysOnColumn(Table $table, string $column): void
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            if (in_array($column, array_map(strtolower(...), $foreignKey->getLocalColumns()), true)) {
                $table->removeForeignKey($foreignKey->getName());
            }
        }
    }

    private function dropCheckConstraintIfSupported(string $table, string $constraint): void
    {
        if (! $this->platformSupportsCheckConstraints()) {
            return;
        }

        $sql = sprintf('ALTER TABLE %s %s %s', $table, $this->dropClause(), $constraint);

        try {
            $this->connection->executeStatement($sql);
        } catch (Exception) {
            // Best-effort: the constraint may not exist on this platform/version. The
            // ExactlyOneDocumentValidator remains the canonical enforcement.
        }
    }

    /**
     * @throws Exception
     */
    private function addCheckConstraintIfSupported(string $table, string $constraint, string ...$columns): void
    {
        $sql = $this->buildExactlyOneNullCheck($table, $constraint, ...$columns);

        if ($sql === null) {
            return;
        }

        try {
            $this->connection->executeStatement($sql);
        } catch (Exception) {
            // Best-effort: older MySQL silently ignores CHECK constraints. The
            // ExactlyOneDocumentValidator remains the canonical enforcement.
        }
    }

    /**
     * `DROP CHECK` on MySQL/MariaDB (`DROP CONSTRAINT` there requires MySQL 8.0.19+, while
     * `DROP CHECK` works from the version that introduced CHECK constraints at all), `DROP
     * CONSTRAINT` everywhere else that supports it. Mirrors the platform support in
     * {@see self::buildExactlyOneNullCheck()}.
     */
    private function dropClause(): string
    {
        return $this->platform instanceof AbstractMySQLPlatform ? 'DROP CHECK' : 'DROP CONSTRAINT';
    }

    private function platformSupportsCheckConstraints(): bool
    {
        if ($this->platform instanceof SQLitePlatform) {
            return false;
        }

        return $this->platform instanceof AbstractMySQLPlatform
            || $this->platform instanceof PostgreSQLPlatform
            || $this->platform instanceof OraclePlatform
            || (class_exists(SQLServerPlatform::class) && $this->platform instanceof SQLServerPlatform);
    }

    /**
     * Build a portable `ALTER TABLE ADD CONSTRAINT CHECK` for the "exactly one is NOT NULL"
     * invariant across an arbitrary set of columns. Copied from
     * {@see \DoctrineMigrations\Version30000_9::buildExactlyOneNullCheck()}, which is private to
     * that migration and not reusable across files by design — each migration stays frozen.
     *
     * Returns null when the current platform either does not support adding CHECK constraints
     * via ALTER TABLE (SQLite) or is not yet covered by this migration.
     */
    private function buildExactlyOneNullCheck(string $table, string $constraint, string ...$columns): ?string
    {
        if (! $this->platformSupportsCheckConstraints()) {
            return null;
        }

        if (count($columns) < 2) {
            return null;
        }

        $branches = [];
        foreach ($columns as $notNull) {
            $parts = [];
            foreach ($columns as $col) {
                $parts[] = sprintf('%s IS %s NULL', $col, $col === $notNull ? 'NOT' : '');
            }

            $branches[] = '(' . implode(' AND ', $parts) . ')';
        }

        return sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s)',
            $table,
            $constraint,
            implode(' OR ', $branches),
        );
    }
}
