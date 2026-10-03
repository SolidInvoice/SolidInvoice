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
use DoctrineMigrations\Version30100_12;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function sprintf;

/**
 * `postUp()` is where every issued invoice and quote is pinned to
 * {@see \SolidInvoice\CoreBundle\Enum\TaxArithmeticVersion::Legacy}, so an issued document is
 * never recalculated under a later arithmetic.
 *
 * @see Version30100_12
 */
final class TaxArithmeticVersionBackfillMigrationTest extends TestCase
{
    private const array TABLES = ['invoices', 'quotes', 'recurring_invoices'];

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }

    public function testUpAddsTheColumnAsNullable(): void
    {
        $schema = $this->preMigrationSchema();

        $this->migration()->up($schema);

        foreach (self::TABLES as $table) {
            $column = $schema->getTable($table)->getColumn('tax_arithmetic_version');

            self::assertFalse($column->getNotnull(), $table);
        }
    }

    public function testDownDropsTheColumn(): void
    {
        $schema = $this->preMigrationSchema();
        $migration = $this->migration();

        $migration->up($schema);
        $migration->down($schema);

        foreach (self::TABLES as $table) {
            self::assertFalse($schema->getTable($table)->hasColumn('tax_arithmetic_version'), $table);
        }
    }

    /**
     * @throws Exception
     */
    public function testPostUpPinsIssuedInvoicesAndQuotesAndLeavesNewAndDraftRowsNull(): void
    {
        $this->createTable('invoices');
        $this->createTable('quotes');

        foreach (['invoices', 'quotes'] as $table) {
            $this->insertRow($table, 'new-1', 'new');
            $this->insertRow($table, 'draft-1', 'draft');
            $this->insertRow($table, 'pending-1', 'pending');
            $this->insertRow($table, 'cancelled-1', 'cancelled');
            $this->insertRow($table, 'archived-1', 'archived');
        }

        $this->migration()->postUp(new Schema());

        foreach (['invoices', 'quotes'] as $table) {
            self::assertNull($this->versionOf($table, 'new-1'), $table);
            self::assertNull($this->versionOf($table, 'draft-1'), $table);
            self::assertSame(1, $this->versionOf($table, 'pending-1'), $table);
            self::assertSame(1, $this->versionOf($table, 'cancelled-1'), $table);
            self::assertSame(1, $this->versionOf($table, 'archived-1'), $table);
        }
    }

    /**
     * A template is never issued: `postUp()` must not touch `recurring_invoices` at all.
     *
     * @throws Exception
     */
    public function testPostUpLeavesRecurringInvoicesUntouched(): void
    {
        // postUp() always backfills invoices and quotes too, so both have to exist even
        // though this test only asserts on recurring_invoices.
        $this->createTable('invoices');
        $this->createTable('quotes');
        $this->createTable('recurring_invoices');
        $this->insertRow('recurring_invoices', 'recurring-1', 'active');

        $this->migration()->postUp(new Schema());

        self::assertNull($this->versionOf('recurring_invoices', 'recurring-1'));
    }

    private function createTable(string $table): void
    {
        $this->connection->executeStatement(
            sprintf(
                'CREATE TABLE %s (id VARCHAR(26) NOT NULL PRIMARY KEY, status VARCHAR(25) NOT NULL, tax_arithmetic_version SMALLINT DEFAULT NULL)',
                $table,
            )
        );
    }

    private function insertRow(string $table, string $id, string $status): void
    {
        $this->connection->insert($table, ['id' => $id, 'status' => $status]);
    }

    /**
     * @throws Exception
     */
    private function versionOf(string $table, string $id): ?int
    {
        $value = $this->connection->fetchOne(
            sprintf('SELECT tax_arithmetic_version FROM %s WHERE id = ?', $table),
            [$id],
        );

        return $value === null ? null : (int) $value;
    }

    private function preMigrationSchema(): Schema
    {
        $schema = new Schema();

        foreach (self::TABLES as $tableName) {
            $schema->createTable($tableName);
        }

        return $schema;
    }

    private function migration(): Version30100_12
    {
        return new Version30100_12($this->connection, new NullLogger());
    }
}
