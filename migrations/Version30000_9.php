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
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final class Version30000_9 extends AbstractMigration
{
    private const COMPANY_VAT_SETTING_KEY = 'system/company/vat_number';

    public function getDescription(): string
    {
        return 'Tax overhaul foundations: extend tax_rates (category/compound), introduce tax_identifier with VAT backfill, introduce line_tax with per-line snapshots, and introduce invoice_tax with document-level snapshots plus withholding/payable totals';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        // The tax_identifier and line_tax tables are created in up(); to backfill from
        // columns dropped in up(), we must materialise the source data first so postUp
        // can perform inserts after the tables exist.
        $this->capturedClientVatNumbers = $this->fetchClientVatNumbers($schema);
        $this->capturedCompanyVatNumbers = $this->fetchCompanyVatNumbers();
        $this->capturedInvoiceLineTaxes = $this->fetchLineTaxes($schema, InvoiceLine::TABLE_NAME, 'invoices');
        $this->capturedQuoteLineTaxes = $this->fetchLineTaxes($schema, QuoteLine::TABLE_NAME, 'quotes');
    }

    public function up(Schema $schema): void
    {
        $taxTable = $schema->getTable(Tax::TABLE_NAME);

        if (! $taxTable->hasColumn('category')) {
            $taxTable->addColumn('category', Types::STRING, [
                'length' => 32,
                'notnull' => true,
                'default' => TaxCategory::Standard->value,
            ]);
        }

        if (! $taxTable->hasColumn('compound')) {
            $taxTable->addColumn('compound', Types::BOOLEAN, [
                'notnull' => true,
                'default' => false,
            ]);
        }

        if (! $schema->hasTable(TaxIdentifier::TABLE_NAME)) {
            $table = $schema->createTable(TaxIdentifier::TABLE_NAME);

            $table->addColumn('id', UlidType::NAME);
            $table->addColumn('company_id', UlidType::NAME);
            $table->addColumn('client_id', UlidType::NAME, ['notnull' => false]);
            $table->addColumn('label', Types::STRING, ['length' => 32, 'notnull' => true]);
            $table->addColumn('value', Types::STRING, ['length' => 64, 'notnull' => true]);
            $table->addColumn('is_primary', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $table->addColumn('created', Types::DATETIME_MUTABLE);
            $table->addColumn('updated', Types::DATETIME_MUTABLE);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['company_id']);
            $table->addIndex(['client_id']);

            $table->addForeignKeyConstraint(
                'companies',
                ['company_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
            );

            $table->addForeignKeyConstraint(
                'clients',
                ['client_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
            );
        }

        $clientTable = $schema->getTable('clients');
        if ($clientTable->hasColumn('vat_number')) {
            $clientTable->dropColumn('vat_number');
        }

        if (! $schema->hasTable(LineTax::TABLE_NAME)) {
            $lineTax = $schema->createTable(LineTax::TABLE_NAME);

            $lineTax->addColumn('id', UlidType::NAME);
            $lineTax->addColumn('company_id', UlidType::NAME);
            $lineTax->addColumn('tax_id', UlidType::NAME, ['notnull' => false]);
            $lineTax->addColumn('invoice_line_id', UlidType::NAME, ['notnull' => false]);
            $lineTax->addColumn('quote_line_id', UlidType::NAME, ['notnull' => false]);
            $lineTax->addColumn('name_snapshot', Types::STRING, ['length' => 32]);
            $lineTax->addColumn('rate_snapshot', Types::DECIMAL, ['precision' => 10, 'scale' => 4]);
            $lineTax->addColumn('category_snapshot', Types::STRING, [
                'length' => 32,
                'default' => TaxCategory::Standard->value,
            ]);
            $lineTax->addColumn('type_snapshot', Types::STRING, ['length' => 32]);
            $lineTax->addColumn('compound', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $lineTax->addColumn('sequence', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
            $lineTax->addColumn('amount', BigIntegerType::NAME, ['notnull' => true]);
            $lineTax->addColumn('snapshotted_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
            $lineTax->addColumn('created', Types::DATETIME_MUTABLE);
            $lineTax->addColumn('updated', Types::DATETIME_MUTABLE);

            $lineTax->setPrimaryKey(['id']);
            $lineTax->addIndex(['company_id']);
            $lineTax->addIndex(['tax_id']);
            $lineTax->addIndex(['invoice_line_id']);
            $lineTax->addIndex(['quote_line_id']);

            $lineTax->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
            $lineTax->addForeignKeyConstraint(Tax::TABLE_NAME, ['tax_id'], ['id'], ['onDelete' => 'SET NULL']);
            $lineTax->addForeignKeyConstraint(InvoiceLine::TABLE_NAME, ['invoice_line_id'], ['id'], ['onDelete' => 'CASCADE']);
            $lineTax->addForeignKeyConstraint(QuoteLine::TABLE_NAME, ['quote_line_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        if (! $schema->hasTable(InvoiceTax::TABLE_NAME)) {
            $invoiceTax = $schema->createTable(InvoiceTax::TABLE_NAME);

            $invoiceTax->addColumn('id', UlidType::NAME);
            $invoiceTax->addColumn('company_id', UlidType::NAME);
            $invoiceTax->addColumn('tax_id', UlidType::NAME, ['notnull' => false]);
            $invoiceTax->addColumn('invoice_id', UlidType::NAME, ['notnull' => false]);
            $invoiceTax->addColumn('quote_id', UlidType::NAME, ['notnull' => false]);
            $invoiceTax->addColumn('recurring_invoice_id', UlidType::NAME, ['notnull' => false]);
            $invoiceTax->addColumn('direction', Types::STRING, [
                'length' => 32,
                'default' => TaxDirection::Additive->value,
            ]);
            $invoiceTax->addColumn('name_snapshot', Types::STRING, ['length' => 32]);
            $invoiceTax->addColumn('rate_snapshot', Types::DECIMAL, ['precision' => 10, 'scale' => 4]);
            $invoiceTax->addColumn('category_snapshot', Types::STRING, [
                'length' => 32,
                'default' => TaxCategory::Standard->value,
            ]);
            $invoiceTax->addColumn('type_snapshot', Types::STRING, [
                'length' => 32,
                'default' => TaxType::Exclusive->value,
            ]);
            $invoiceTax->addColumn('amount', BigIntegerType::NAME, ['notnull' => true]);
            $invoiceTax->addColumn('note', Types::TEXT, ['notnull' => false]);
            $invoiceTax->addColumn('sequence', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
            $invoiceTax->addColumn('snapshotted_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
            $invoiceTax->addColumn('created', Types::DATETIME_MUTABLE);
            $invoiceTax->addColumn('updated', Types::DATETIME_MUTABLE);

            $invoiceTax->setPrimaryKey(['id']);
            $invoiceTax->addIndex(['company_id']);
            $invoiceTax->addIndex(['tax_id']);
            $invoiceTax->addIndex(['invoice_id']);
            $invoiceTax->addIndex(['quote_id']);
            $invoiceTax->addIndex(['recurring_invoice_id']);

            $invoiceTax->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
            $invoiceTax->addForeignKeyConstraint(Tax::TABLE_NAME, ['tax_id'], ['id'], ['onDelete' => 'SET NULL']);
            $invoiceTax->addForeignKeyConstraint(Invoice::TABLE_NAME, ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);
            $invoiceTax->addForeignKeyConstraint(Quote::TABLE_NAME, ['quote_id'], ['id'], ['onDelete' => 'CASCADE']);
            $invoiceTax->addForeignKeyConstraint(RecurringInvoice::TABLE_NAME, ['recurring_invoice_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        if ($schema->hasTable(InvoiceTax::TABLE_NAME)) {
            $existingInvoiceTax = $schema->getTable(InvoiceTax::TABLE_NAME);
            if (! $existingInvoiceTax->hasColumn('type_snapshot')) {
                $existingInvoiceTax->addColumn('type_snapshot', Types::STRING, [
                    'length' => 32,
                    'default' => TaxType::Exclusive->value,
                ]);
            }
        }

        $this->addDocumentTotalsColumns($schema, Invoice::TABLE_NAME);
        $this->addDocumentTotalsColumns($schema, RecurringInvoice::TABLE_NAME);
        $this->addDocumentTotalsColumns($schema, Quote::TABLE_NAME);

        $invoiceLines = $schema->getTable(InvoiceLine::TABLE_NAME);
        if ($invoiceLines->hasColumn('tax_id')) {
            // Drop FK before column so doctrine doesn't complain on platforms that require it.
            foreach ($invoiceLines->getForeignKeys() as $fk) {
                if (in_array('tax_id', array_map('strtolower', $fk->getLocalColumns()), true)) {
                    $invoiceLines->removeForeignKey($fk->getName());
                }
            }
            $invoiceLines->dropColumn('tax_id');
        }

        $quoteLines = $schema->getTable(QuoteLine::TABLE_NAME);
        if ($quoteLines->hasColumn('tax_id')) {
            foreach ($quoteLines->getForeignKeys() as $fk) {
                if (in_array('tax_id', array_map('strtolower', $fk->getLocalColumns()), true)) {
                    $quoteLines->removeForeignKey($fk->getName());
                }
            }
            $quoteLines->dropColumn('tax_id');
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($this->capturedClientVatNumbers as $row) {
            $this->connection->insert(TaxIdentifier::TABLE_NAME, [
                'id' => (new Ulid())->toBinary(),
                'company_id' => $row['company_id'],
                'client_id' => $row['id'],
                'label' => 'VAT',
                'value' => $row['vat_number'],
                'is_primary' => true,
                'created' => $now,
                'updated' => $now,
            ]);
        }

        foreach ($this->capturedCompanyVatNumbers as $row) {
            $this->connection->insert(TaxIdentifier::TABLE_NAME, [
                'id' => (new Ulid())->toBinary(),
                'company_id' => $row['company_id'],
                'client_id' => null,
                'label' => 'VAT',
                'value' => $row['setting_value'],
                'is_primary' => true,
                'created' => $now,
                'updated' => $now,
            ]);
        }

        $this->connection->delete('app_config', [
            'setting_key' => self::COMPANY_VAT_SETTING_KEY,
        ]);

        $this->backfillLineTaxes($this->capturedInvoiceLineTaxes, 'invoice_line_id', $now);
        $this->backfillLineTaxes($this->capturedQuoteLineTaxes, 'quote_line_id', $now);

        $this->addLineTaxCheckConstraint();
        $this->addInvoiceTaxCheckConstraint();
    }

    public function down(Schema $schema): void
    {
        $taxTable = $schema->getTable(Tax::TABLE_NAME);

        if ($taxTable->hasColumn('compound')) {
            $taxTable->dropColumn('compound');
        }

        if ($taxTable->hasColumn('category')) {
            $taxTable->dropColumn('category');
        }

        if ($schema->hasTable(InvoiceTax::TABLE_NAME)) {
            $schema->dropTable(InvoiceTax::TABLE_NAME);
        }

        $this->dropDocumentTotalsColumns($schema, Invoice::TABLE_NAME);
        $this->dropDocumentTotalsColumns($schema, RecurringInvoice::TABLE_NAME);
        $this->dropDocumentTotalsColumns($schema, Quote::TABLE_NAME);

        if ($schema->hasTable(LineTax::TABLE_NAME)) {
            $schema->dropTable(LineTax::TABLE_NAME);
        }

        $invoiceLines = $schema->getTable(InvoiceLine::TABLE_NAME);
        if (! $invoiceLines->hasColumn('tax_id')) {
            $invoiceLines->addColumn('tax_id', UlidType::NAME, ['notnull' => false]);
            $invoiceLines->addForeignKeyConstraint(Tax::TABLE_NAME, ['tax_id'], ['id'], ['onDelete' => 'SET NULL']);
        }

        $quoteLines = $schema->getTable(QuoteLine::TABLE_NAME);
        if (! $quoteLines->hasColumn('tax_id')) {
            $quoteLines->addColumn('tax_id', UlidType::NAME, ['notnull' => false]);
            $quoteLines->addForeignKeyConstraint(Tax::TABLE_NAME, ['tax_id'], ['id'], ['onDelete' => 'SET NULL']);
        }

        if ($schema->hasTable(TaxIdentifier::TABLE_NAME)) {
            $schema->dropTable(TaxIdentifier::TABLE_NAME);
        }

        $clientTable = $schema->getTable('clients');
        if (! $clientTable->hasColumn('vat_number')) {
            $clientTable->addColumn('vat_number', Types::STRING, [
                'length' => 255,
                'notnull' => false,
            ]);
        }
    }

    /**
     * @var list<array{id: string, company_id: string, vat_number: string}>
     */
    private array $capturedClientVatNumbers = [];

    /**
     * @var list<array{company_id: string, setting_value: string}>
     */
    private array $capturedCompanyVatNumbers = [];

    /**
     * @var list<array{line_id: string, parent_company_id: string, tax_id: string, tax_name: string, tax_rate: float|int|string, tax_type_value: string, tax_category: string|null, tax_compound: int|bool|null, snapshotted_at: string|null}>
     */
    private array $capturedInvoiceLineTaxes = [];

    /**
     * @var list<array{line_id: string, parent_company_id: string, tax_id: string, tax_name: string, tax_rate: float|int|string, tax_type_value: string, tax_category: string|null, tax_compound: int|bool|null, snapshotted_at: string|null}>
     */
    private array $capturedQuoteLineTaxes = [];

    /**
     * @return list<array{id: string, company_id: string, vat_number: string}>
     * @throws Exception
     */
    private function fetchClientVatNumbers(Schema $schema): array
    {
        $clientTable = $schema->getTable('clients');
        if (! $clientTable->hasColumn('vat_number')) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder()
            ->select('id', 'company_id', 'vat_number')
            ->from('clients')
            ->where('vat_number IS NOT NULL');

        /** @var list<array{id: string, company_id: string, vat_number: mixed}> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        // Filter empty strings in PHP — Oracle conflates '' with NULL anyway, but other
        // platforms allow empty strings through the IS NOT NULL guard.
        return array_values(array_filter(
            array_map(
                static fn (array $row): array => [
                    'id' => $row['id'],
                    'company_id' => $row['company_id'],
                    'vat_number' => (string) $row['vat_number'],
                ],
                $rows,
            ),
            static fn (array $row): bool => $row['vat_number'] !== '',
        ));
    }

    /**
     * @return list<array{company_id: string, setting_value: string}>
     * @throws Exception
     */
    private function fetchCompanyVatNumbers(): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('company_id', 'setting_value')
            ->from('app_config')
            ->where('setting_key = :key')
            ->andWhere('setting_value IS NOT NULL')
            ->setParameter('key', self::COMPANY_VAT_SETTING_KEY);

        /** @var list<array{company_id: string, setting_value: mixed}> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_values(array_filter(
            array_map(
                static fn (array $row): array => [
                    'company_id' => $row['company_id'],
                    'setting_value' => (string) $row['setting_value'],
                ],
                $rows,
            ),
            static fn (array $row): bool => $row['setting_value'] !== '',
        ));
    }

    /**
     * @return list<array{line_id: string, parent_company_id: string, tax_id: string, tax_name: string, tax_rate: float|int|string, tax_type_value: string, tax_category: string|null, tax_compound: int|bool|null, snapshotted_at: string|null}>
     * @throws Exception
     */
    private function fetchLineTaxes(Schema $schema, string $lineTable, string $parentTable): array
    {
        if (! $schema->hasTable($lineTable)) {
            return [];
        }

        $lines = $schema->getTable($lineTable);
        if (! $lines->hasColumn('tax_id')) {
            return [];
        }

        // Doctrine's default FK column name follows {assoc}_id (e.g., invoice_id, quote_id),
        // but we resolve dynamically so the migration is robust against any historical naming.
        $parentColumn = $this->resolveParentForeignKeyColumn($schema, $lineTable, $parentTable);
        if ($parentColumn === null) {
            return [];
        }

        // Fetch each table separately and join in PHP — avoids reserved-word aliases, CASE
        // expressions, and dialect-specific join behaviour entirely.
        $lineRows = $this->connection->createQueryBuilder()
            ->select('id', 'company_id', 'tax_id', $parentColumn)
            ->from($lineTable)
            ->where('tax_id IS NOT NULL')
            ->executeQuery()
            ->fetchAllAssociative();

        if ($lineRows === []) {
            return [];
        }

        $taxes = $this->loadTaxesById($schema);
        $parents = $this->loadParentsById($parentTable);

        $result = [];

        foreach ($lineRows as $line) {
            $taxId = (string) $line['tax_id'];
            $tax = $taxes[$taxId] ?? null;
            if ($tax === null) {
                // The line points at a tax that no longer exists; skip.
                continue;
            }

            $parent = $parents[(string) $line[$parentColumn]] ?? null;
            $snapshottedAt = null;
            if ($parent !== null && $parent['status'] !== 'draft') {
                $snapshottedAt = $parent['updated'];
            }

            $result[] = [
                'line_id' => (string) $line['id'],
                'parent_company_id' => (string) $line['company_id'],
                'tax_id' => $taxId,
                'tax_name' => (string) $tax['name'],
                'tax_rate' => $tax['rate'],
                'tax_type_value' => (string) $tax['tax_type'],
                'tax_category' => $tax['category'] !== null ? (string) $tax['category'] : null,
                'tax_compound' => $tax['compound'],
                'snapshotted_at' => $snapshottedAt,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array{name: string, rate: float|int|string, tax_type: string, category: string|null, compound: int|bool|null}>
     * @throws Exception
     */
    private function loadTaxesById(Schema $schema): array
    {
        $taxesTable = Tax::TABLE_NAME;
        $hasCategory = $schema->getTable($taxesTable)->hasColumn('category');
        $hasCompound = $schema->getTable($taxesTable)->hasColumn('compound');

        $columns = ['id', 'name', 'rate', 'tax_type'];
        if ($hasCategory) {
            $columns[] = 'category';
        }
        if ($hasCompound) {
            $columns[] = 'compound';
        }

        $rows = $this->connection->createQueryBuilder()
            ->select(...$columns)
            ->from($taxesTable)
            ->executeQuery()
            ->fetchAllAssociative();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['id']] = [
                'name' => (string) $row['name'],
                'rate' => $row['rate'],
                'tax_type' => (string) $row['tax_type'],
                'category' => $hasCategory ? ($row['category'] ?? null) : null,
                'compound' => $hasCompound ? ($row['compound'] ?? null) : null,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{status: string, updated: string|null}>
     * @throws Exception
     */
    private function loadParentsById(string $parentTable): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'status', 'updated')
            ->from($parentTable)
            ->executeQuery()
            ->fetchAllAssociative();

        $out = [];
        foreach ($rows as $row) {
            $updated = $row['updated'];
            if ($updated instanceof DateTimeImmutable) {
                $updated = $updated->format('Y-m-d H:i:s');
            } elseif ($updated !== null) {
                $updated = (string) $updated;
            }

            $out[(string) $row['id']] = [
                'status' => (string) $row['status'],
                'updated' => $updated,
            ];
        }

        return $out;
    }

    private function resolveParentForeignKeyColumn(Schema $schema, string $lineTable, string $parentTable): ?string
    {
        $line = $schema->getTable($lineTable);

        foreach ($line->getForeignKeys() as $fk) {
            if (strtolower($fk->getForeignTableName()) === $parentTable) {
                $columns = $fk->getLocalColumns();
                if ($columns !== []) {
                    return $columns[0];
                }
            }
        }

        return null;
    }

    /**
     * @param list<array{line_id: string, parent_company_id: string, tax_id: string, tax_name: string, tax_rate: float|int|string, tax_type_value: string, tax_category: string|null, tax_compound: int|bool|null, snapshotted_at: string|null}> $rows
     * @throws Exception
     */
    private function backfillLineTaxes(array $rows, string $lineColumn, string $now): void
    {
        foreach ($rows as $row) {
            $type = TaxType::tryFrom((string) $row['tax_type_value']) ?? TaxType::Exclusive;
            $category = TaxCategory::tryFrom((string) ($row['tax_category'] ?? '')) ?? TaxCategory::Standard;

            $this->connection->insert(LineTax::TABLE_NAME, [
                'id' => (new Ulid())->toBinary(),
                'company_id' => $row['parent_company_id'],
                'tax_id' => $row['tax_id'],
                'invoice_line_id' => $lineColumn === 'invoice_line_id' ? $row['line_id'] : null,
                'quote_line_id' => $lineColumn === 'quote_line_id' ? $row['line_id'] : null,
                'name_snapshot' => (string) $row['tax_name'],
                'rate_snapshot' => $this->normaliseRate($row['tax_rate']),
                'category_snapshot' => $category->value,
                'type_snapshot' => $type->value,
                'compound' => $row['tax_compound'] ? 1 : 0,
                'sequence' => 0,
                'amount' => 0,
                'snapshotted_at' => $row['snapshotted_at'],
                'created' => $now,
                'updated' => $now,
            ]);
        }
    }

    private function normaliseRate(float|int|string $rate): string
    {
        // tax_rates.rate is float in the legacy schema; force 4-decimal text representation.
        return number_format((float) $rate, 4, '.', '');
    }

    private function addDocumentTotalsColumns(Schema $schema, string $tableName): void
    {
        if (! $schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);

        if (! $table->hasColumn('withholding_amount')) {
            $table->addColumn('withholding_amount', BigIntegerType::NAME, [
                'notnull' => true,
                'default' => 0,
            ]);
        }

        if (! $table->hasColumn('payable_amount')) {
            $table->addColumn('payable_amount', BigIntegerType::NAME, [
                'notnull' => true,
                'default' => 0,
            ]);
        }
    }

    private function dropDocumentTotalsColumns(Schema $schema, string $tableName): void
    {
        if (! $schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);

        if ($table->hasColumn('payable_amount')) {
            $table->dropColumn('payable_amount');
        }

        if ($table->hasColumn('withholding_amount')) {
            $table->dropColumn('withholding_amount');
        }
    }

    private function addInvoiceTaxCheckConstraint(): void
    {
        $sql = $this->buildExactlyOneNullCheck(
            'invoice_tax',
            'invoice_tax_exactly_one_document',
            'invoice_id',
            'quote_id',
            'recurring_invoice_id',
        );

        if ($sql === null) {
            return;
        }

        try {
            $this->connection->executeStatement($sql);
        } catch (Exception) {
            // Best-effort: older MySQL silently ignores CHECK constraints. The
            // ExactlyOneDocument validator remains the canonical enforcement.
        }
    }

    private function addLineTaxCheckConstraint(): void
    {
        $sql = $this->buildExactlyOneNullCheck(
            'line_tax',
            'line_tax_exactly_one_line',
            'invoice_line_id',
            'quote_line_id',
        );

        if ($sql === null) {
            return;
        }

        try {
            $this->connection->executeStatement($sql);
        } catch (Exception) {
            // Best-effort: older MySQL versions silently ignore CHECK constraints. The
            // ExactlyOneLine validator remains the canonical enforcement.
        }
    }

    /**
     * Build a portable `ALTER TABLE ADD CONSTRAINT CHECK` for the "exactly one is NOT NULL"
     * invariant across an arbitrary set of columns.
     *
     * Returns null when the current platform either does not support adding CHECK constraints
     * via ALTER TABLE (SQLite) or is not yet covered by this migration (anything other than
     * MySQL/MariaDB, PostgreSQL, Oracle, or SQL Server).
     *
     * The CHECK expression is the long-form disjunction `(a IS NOT NULL AND b IS NULL AND ...)`
     * rather than `(a IS NULL) <> (b IS NULL)`: the latter compares boolean expressions, which
     * is rejected by Oracle and SQL Server because they don't have a SQL-level boolean type.
     * The long form is standard SQL and is accepted by every supported platform.
     */
    private function buildExactlyOneNullCheck(string $table, string $constraint, string ...$columns): ?string
    {
        // SQLite cannot ALTER TABLE ADD CONSTRAINT; application-level validators cover it.
        if ($this->platform instanceof SqlitePlatform) {
            return null;
        }

        if (count($columns) < 2) {
            return null;
        }

        // Doctrine's MySQLPlatform covers MariaDB; PostgreSQLPlatform covers PostgreSQL.
        // Oracle and SQL Server also support `ALTER TABLE ADD CONSTRAINT CHECK`, so include
        // them so the invariant is enforced at the DB level on every server-class platform.
        $supported = $this->platform instanceof MySQLPlatform
            || $this->platform instanceof PostgreSQLPlatform
            || $this->platform instanceof OraclePlatform
            || (class_exists(\Doctrine\DBAL\Platforms\SQLServerPlatform::class)
                && $this->platform instanceof \Doctrine\DBAL\Platforms\SQLServerPlatform);

        if (! $supported) {
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
