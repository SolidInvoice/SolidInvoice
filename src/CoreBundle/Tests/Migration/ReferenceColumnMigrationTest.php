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
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version30200_1;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReferenceColumnMigrationTest extends TestCase
{
    private const array DOCUMENT_TABLES = ['invoices', 'recurring_invoices', 'quotes'];

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function documentTables(): iterable
    {
        foreach (self::DOCUMENT_TABLES as $table) {
            yield $table => [$table];
        }
    }

    #[DataProvider('documentTables')]
    public function testUpAddsTheReferenceColumn(string $table): void
    {
        $schema = $this->preMigrationSchema();

        $this->migration()->up($schema);

        $column = $schema->getTable($table)->getColumn('reference');

        self::assertFalse($column->getNotnull());
        self::assertSame(255, $column->getLength());
    }

    public function testUpAddsTheDefaultReferenceColumnToClients(): void
    {
        $schema = $this->preMigrationSchema();

        $this->migration()->up($schema);

        $column = $schema->getTable('clients')->getColumn('default_reference');

        self::assertFalse($column->getNotnull());
        self::assertSame(255, $column->getLength());
    }

    public function testUpIsIdempotent(): void
    {
        $schema = $this->preMigrationSchema();
        $migration = $this->migration();

        $migration->up($schema);
        $migration->up($schema);

        self::assertTrue($schema->getTable('invoices')->hasColumn('reference'));
        self::assertTrue($schema->getTable('clients')->hasColumn('default_reference'));
    }

    #[DataProvider('documentTables')]
    public function testDownDropsTheReferenceColumn(string $table): void
    {
        $schema = $this->preMigrationSchema();
        $migration = $this->migration();

        $migration->up($schema);
        $migration->down($schema);

        self::assertFalse($schema->getTable($table)->hasColumn('reference'));
    }

    public function testDownDropsTheDefaultReferenceColumnFromClients(): void
    {
        $schema = $this->preMigrationSchema();
        $migration = $this->migration();

        $migration->up($schema);
        $migration->down($schema);

        self::assertFalse($schema->getTable('clients')->hasColumn('default_reference'));
    }

    public function testDownIsIdempotentWhenTheColumnIsAlreadyAbsent(): void
    {
        $schema = $this->preMigrationSchema();
        $migration = $this->migration();

        $migration->down($schema);
        $migration->down($schema);

        self::assertFalse($schema->getTable('invoices')->hasColumn('reference'));
        self::assertFalse($schema->getTable('clients')->hasColumn('default_reference'));
    }

    private function preMigrationSchema(): Schema
    {
        $schema = new Schema();

        foreach (self::DOCUMENT_TABLES as $tableName) {
            $schema->createTable($tableName);
        }

        $schema->createTable('clients');

        return $schema;
    }

    private function migration(): Version30200_1
    {
        return new Version30200_1($this->connection, new NullLogger());
    }
}
