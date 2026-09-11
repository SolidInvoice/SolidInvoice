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
use Doctrine\DBAL\Types\Types;
use DoctrineMigrations\Version30100_6;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function mb_strlen;
use function sprintf;
use function str_repeat;

final class LineNameMigrationTest extends TestCase
{
    private const array TABLES = ['invoice_lines', 'quote_lines'];

    private const int NAME_MAX_LENGTH = 255;

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
    public static function tables(): iterable
    {
        foreach (self::TABLES as $table) {
            yield $table => [$table];
        }
    }

    #[DataProvider('tables')]
    public function testUpAddsTheNameColumnAndRelaxesTheDescription(string $table): void
    {
        $schema = $this->preMigrationSchema();

        $this->migration()->up($schema);

        $columns = $schema->getTable($table);

        self::assertTrue($columns->getColumn('name')->getNotnull());
        self::assertSame(self::NAME_MAX_LENGTH, $columns->getColumn('name')->getLength());
        self::assertFalse($columns->getColumn('description')->getNotnull());
    }

    #[DataProvider('tables')]
    public function testDownDropsTheNameColumnAndRequiresTheDescriptionAgain(string $table): void
    {
        $schema = $this->preMigrationSchema();
        $migration = $this->migration();

        $migration->up($schema);
        $migration->down($schema);

        self::assertFalse($schema->getTable($table)->hasColumn('name'));
        self::assertTrue($schema->getTable($table)->getColumn('description')->getNotnull());
    }

    /**
     * The corpus the acceptance criteria ask for: short, long, multi-line and blank. Nothing
     * a user wrote may be lost, so every case asserts what happened to `description` too.
     *
     * @return iterable<string, array{string, string, ?string}>
     */
    public static function corpus(): iterable
    {
        yield 'short single line moves into the name' => [
            'Website design',
            'Website design',
            null,
        ];

        yield 'multi line keeps the detail' => [
            "Website design\nIncluding two rounds of revisions.",
            'Website design',
            "Website design\nIncluding two rounds of revisions.",
        ];

        yield 'long prose is truncated and kept in full' => [
            str_repeat('a', 400),
            str_repeat('a', self::NAME_MAX_LENGTH - 1) . '…',
            str_repeat('a', 400),
        ];

        yield 'trailing whitespace is not data' => [
            "Website design\n",
            'Website design',
            "Website design\n",
        ];

        yield 'blank description names nothing' => [
            '   ',
            '',
            '   ',
        ];
    }

    #[DataProvider('corpus')]
    public function testPostUpBackfillsNamesWithoutLosingAnything(
        string $description,
        string $expectedName,
        ?string $expectedDescription
    ): void {
        foreach (self::TABLES as $table) {
            $this->createTable($table);
            $this->connection->insert($table, ['id' => 'line-1', 'description' => $description, 'name' => '']);
        }

        $this->migration()->postUp(new Schema());

        foreach (self::TABLES as $table) {
            $row = $this->connection->fetchAssociative(sprintf('SELECT name, description FROM %s', $table));

            self::assertIsArray($row);
            self::assertSame($expectedName, $row['name'], $table);
            self::assertSame($expectedDescription, $row['description'], $table);
            self::assertLessThanOrEqual(self::NAME_MAX_LENGTH, mb_strlen((string) $row['name']), $table);
        }
    }

    /**
     * The backfill pages through the table, and a page is 500 rows. A corpus larger than one
     * page is what proves the cursor advances rather than re-reading the first page forever —
     * including over the rows it deliberately skips.
     */
    public function testPostUpBackfillsEveryPage(): void
    {
        foreach (self::TABLES as $table) {
            $this->createTable($table);
        }

        for ($i = 0; $i < 1200; ++$i) {
            $this->connection->insert('invoice_lines', [
                // A blank description is one of the rows the pass skips, so the cursor has
                // to move past it on its own.
                'id' => sprintf('line-%04d', $i),
                'description' => $i % 100 === 0 ? '' : sprintf('Line %d', $i),
                'name' => '',
            ]);
        }

        $this->migration()->postUp(new Schema());

        self::assertSame(
            12,
            (int) $this->connection->fetchOne("SELECT COUNT(*) FROM invoice_lines WHERE name = ''")
        );
        self::assertSame(
            'Line 1199',
            $this->connection->fetchOne('SELECT name FROM invoice_lines WHERE id = ?', ['line-1199'])
        );
    }

    public function testPreDownPutsAMovedDescriptionBack(): void
    {
        foreach (self::TABLES as $table) {
            $this->createTable($table);
            $this->connection->insert($table, ['id' => 'line-1', 'description' => 'Website design', 'name' => '']);
        }

        $migration = $this->migration();
        $migration->postUp(new Schema());
        $migration->preDown(new Schema());

        foreach (self::TABLES as $table) {
            self::assertSame(
                'Website design',
                $this->connection->fetchOne(sprintf('SELECT description FROM %s', $table)),
                $table
            );
        }
    }

    private function createTable(string $table): void
    {
        $this->connection->executeStatement(
            sprintf('CREATE TABLE %s (id VARCHAR(32) NOT NULL PRIMARY KEY, name VARCHAR(255) NOT NULL, description TEXT NULL)', $table)
        );
    }

    private function preMigrationSchema(): Schema
    {
        $schema = new Schema();

        foreach (self::TABLES as $tableName) {
            $table = $schema->createTable($tableName);
            $table->addColumn('description', Types::TEXT, ['notnull' => true]);
        }

        return $schema;
    }

    private function migration(): Version30100_6
    {
        return new Version30100_6($this->connection, new NullLogger());
    }
}
