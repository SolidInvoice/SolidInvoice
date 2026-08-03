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
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use DoctrineMigrations\Version30100_2;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;

final class LineQuantityDecimalMigrationTest extends TestCase
{
    private const array TABLES = ['invoice_lines', 'quote_lines'];

    /**
     * @return iterable<string, array{string}>
     */
    public static function tables(): iterable
    {
        foreach (self::TABLES as $table) {
            yield $table => [$table];
        }
    }

    #[DataProvider('tables')]
    public function testUpConvertsTheFloatColumnToAnExactDecimal(string $table): void
    {
        $schema = $this->preMigrationSchema();

        $this->migration()->up($schema);

        $column = $schema->getTable($table)->getColumn('qty');

        self::assertSame(Type::getType(QuantityType::NAME), $column->getType());
        // Literals, not QuantityType's constants: the migration freezes the shape it
        // produced, so this has to fail if a later change to the type silently moves it.
        self::assertSame(20, $column->getPrecision());
        self::assertSame(6, $column->getScale());
        self::assertTrue($column->getNotnull());
    }

    #[DataProvider('tables')]
    public function testDownRestoresTheFloatColumn(string $table): void
    {
        $schema = $this->preMigrationSchema();
        $migration = $this->migration();

        $migration->up($schema);
        $migration->down($schema);

        $column = $schema->getTable($table)->getColumn('qty');

        self::assertSame(Type::getType(Types::FLOAT), $column->getType());
        self::assertSame(10, $column->getPrecision());
        self::assertSame(0, $column->getScale());
    }

    private function preMigrationSchema(): Schema
    {
        // The type is registered by the DBAL config in a booted kernel; this test runs
        // without one.
        if (! Type::hasType(QuantityType::NAME)) {
            Type::addType(QuantityType::NAME, QuantityType::class);
        }

        $schema = new Schema();

        foreach (self::TABLES as $tableName) {
            $table = $schema->createTable($tableName);
            $table->addColumn('qty', Types::FLOAT, ['notnull' => true]);
        }

        return $schema;
    }

    private function migration(): Version30100_2
    {
        return new Version30100_2($this->connection(), new NullLogger());
    }

    /**
     * The migration only mutates a {@see Schema}, so the platform is irrelevant to what is
     * asserted here — but AbstractMigration resolves one in its constructor. In-memory
     * SQLite keeps that self-contained: it needs no server, and it is the only driver the
     * suite already requires, whereas `ext-pdo_mysql` is not a declared dependency.
     */
    private function connection(): Connection
    {
        return DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }
}
