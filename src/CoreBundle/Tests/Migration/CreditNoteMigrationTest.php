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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version30100_7;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * `Version30100_7` on SQLite in particular: SQLite cannot add a foreign key to an existing
 * table by `ALTER`, so Doctrine recreates `invoice_lines` and `invoice_tax` under the hood.
 * `invoice_lines` is populated on any real 3.0.x database, so this proves that recreation
 * preserves every existing row and its `type` discriminator value, not just the new column.
 *
 * @see Version30100_7
 */
final class CreditNoteMigrationTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->connection->executeStatement('CREATE TABLE companies (id VARCHAR(26) NOT NULL PRIMARY KEY)');
        $this->connection->executeStatement('CREATE TABLE clients (id VARCHAR(26) NOT NULL PRIMARY KEY)');
        $this->connection->executeStatement('CREATE TABLE invoices (id VARCHAR(26) NOT NULL PRIMARY KEY)');

        $this->connection->executeStatement(
            "CREATE TABLE invoice_lines (id VARCHAR(26) NOT NULL PRIMARY KEY, invoice_id VARCHAR(26) DEFAULT NULL, recurringInvoice_id VARCHAR(26) DEFAULT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL DEFAULT '')"
        );

        $this->connection->executeStatement(
            'CREATE TABLE invoice_tax (id VARCHAR(26) NOT NULL PRIMARY KEY, invoice_id VARCHAR(26) DEFAULT NULL, quote_id VARCHAR(26) DEFAULT NULL, recurring_invoice_id VARCHAR(26) DEFAULT NULL, amount INTEGER NOT NULL DEFAULT 0)'
        );

        $this->connection->insert('companies', ['id' => 'company-1']);
        $this->connection->insert('clients', ['id' => 'client-1']);
        $this->connection->insert('invoices', ['id' => 'invoice-1']);

        $this->connection->insert('invoice_lines', [
            'id' => 'line-invoice',
            'invoice_id' => 'invoice-1',
            'type' => 'invoice',
            'name' => 'An invoice line',
        ]);
        $this->connection->insert('invoice_lines', [
            'id' => 'line-recurring',
            'recurringInvoice_id' => 'invoice-1',
            'type' => 'recurring_invoice',
            'name' => 'A recurring invoice line',
        ]);

        $this->connection->insert('invoice_tax', [
            'id' => 'tax-1',
            'invoice_id' => 'invoice-1',
            'amount' => 500,
        ]);
    }

    /**
     * @throws Exception
     */
    public function testUpCreatesCreditNotesAndAddsTheOwnerColumns(): void
    {
        $this->applyUp();

        $schema = $this->connection->createSchemaManager()->introspectSchema();

        self::assertTrue($schema->hasTable('credit_notes'));
        self::assertTrue($schema->getTable('invoice_lines')->hasColumn('credit_note_id'));
        self::assertTrue($schema->getTable('invoice_tax')->hasColumn('credit_note_id'));
        self::assertFalse($schema->getTable('invoice_lines')->getColumn('credit_note_id')->getNotnull());
        self::assertFalse($schema->getTable('invoice_tax')->getColumn('credit_note_id')->getNotnull());
    }

    /**
     * @throws Exception
     */
    public function testUpPreservesExistingInvoiceLineRowsAndTheirDiscriminator(): void
    {
        $this->applyUp();

        $rows = $this->connection->fetchAllAssociativeIndexed(
            'SELECT id, invoice_id, "recurringInvoice_id" AS recurring_invoice_id, type, name, credit_note_id FROM invoice_lines ORDER BY id'
        );

        self::assertCount(2, $rows);

        self::assertSame('invoice-1', $rows['line-invoice']['invoice_id']);
        self::assertSame('invoice', $rows['line-invoice']['type']);
        self::assertSame('An invoice line', $rows['line-invoice']['name']);
        self::assertNull($rows['line-invoice']['credit_note_id']);

        self::assertSame('invoice-1', $rows['line-recurring']['recurring_invoice_id']);
        self::assertSame('recurring_invoice', $rows['line-recurring']['type']);
        self::assertSame('A recurring invoice line', $rows['line-recurring']['name']);
        self::assertNull($rows['line-recurring']['credit_note_id']);
    }

    /**
     * @throws Exception
     */
    public function testUpPreservesExistingInvoiceTaxRows(): void
    {
        $this->applyUp();

        $row = $this->connection->fetchAssociative('SELECT invoice_id, amount, credit_note_id FROM invoice_tax WHERE id = ?', ['tax-1']);

        self::assertIsArray($row);
        self::assertSame('invoice-1', $row['invoice_id']);
        self::assertSame(500, (int) $row['amount']);
        self::assertNull($row['credit_note_id']);
    }

    /**
     * @throws Exception
     */
    public function testDownDropsCreditNotesAndTheOwnerColumnsWithoutLosingExistingRows(): void
    {
        $this->applyUp();
        $this->applyDown();

        $schema = $this->connection->createSchemaManager()->introspectSchema();

        self::assertFalse($schema->hasTable('credit_notes'));
        self::assertFalse($schema->getTable('invoice_lines')->hasColumn('credit_note_id'));
        self::assertFalse($schema->getTable('invoice_tax')->hasColumn('credit_note_id'));

        $rows = $this->connection->fetchAllAssociativeIndexed('SELECT id, type FROM invoice_lines ORDER BY id');
        self::assertCount(2, $rows);
        self::assertSame('invoice', $rows['line-invoice']['type']);
        self::assertSame('recurring_invoice', $rows['line-recurring']['type']);

        self::assertSame(500, (int) $this->connection->fetchOne('SELECT amount FROM invoice_tax WHERE id = ?', ['tax-1']));
    }

    /**
     * @throws Exception
     */
    private function applyUp(): void
    {
        $fromSchema = $this->connection->createSchemaManager()->introspectSchema();
        $toSchema = clone $fromSchema;

        $migration = $this->migration();
        $migration->up($toSchema);
        $this->executeDiff($fromSchema, $toSchema);
        $migration->postUp($toSchema);
    }

    /**
     * @throws Exception
     */
    private function applyDown(): void
    {
        $fromSchema = $this->connection->createSchemaManager()->introspectSchema();
        $toSchema = clone $fromSchema;

        $migration = $this->migration();
        $migration->preDown($toSchema);
        $migration->down($toSchema);
        $this->executeDiff($fromSchema, $toSchema);
    }

    /**
     * Mirrors what the Doctrine Migrations runner does with the `Schema` a migration mutates:
     * diff it against the schema as introspected before the migration ran, then execute the
     * platform-specific SQL the diff produces — which, on SQLite, is table recreation for any
     * column that needs a new foreign key.
     *
     * @throws Exception
     */
    private function executeDiff(Schema $fromSchema, Schema $toSchema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $comparator = $this->connection->createSchemaManager()->createComparator();
        $diff = $comparator->compareSchemas($fromSchema, $toSchema);

        foreach ($platform->getAlterSchemaSQL($diff) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    private function migration(): Version30100_7
    {
        return new Version30100_7($this->connection, new NullLogger());
    }
}
