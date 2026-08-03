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
    /**
     * @return iterable<string, array{string}>
     */
    public static function tables(): iterable
    {
        yield 'invoice_lines' => ['invoice_lines'];
        yield 'quote_lines' => ['quote_lines'];
    }

    #[DataProvider('tables')]
    public function testUpConvertsTheFloatColumnToAnExactDecimal(string $table): void
    {
        $schema = $this->preMigrationSchema();

        $this->migration()->up($schema);

        $column = $schema->getTable($table)->getColumn('qty');

        self::assertSame(Type::getType(Types::DECIMAL), $column->getType());
        self::assertSame(QuantityType::PRECISION, $column->getPrecision());
        self::assertSame(QuantityType::SCALE, $column->getScale());
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
    }

    private function preMigrationSchema(): Schema
    {
        $schema = new Schema();

        foreach (['invoice_lines', 'quote_lines'] as $tableName) {
            $table = $schema->createTable($tableName);
            $table->addColumn('qty', Types::FLOAT, ['notnull' => true]);
        }

        return $schema;
    }

    private function migration(): Version30100_2
    {
        return new Version30100_2($this->connection(), new NullLogger());
    }

    private function connection(): Connection
    {
        // A server version keeps DBAL from having to connect to resolve the platform.
        return DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => 'solidinvoice',
            'serverVersion' => '8.0.0',
        ]);
    }
}
