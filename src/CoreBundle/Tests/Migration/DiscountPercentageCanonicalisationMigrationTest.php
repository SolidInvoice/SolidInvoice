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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version30100_13;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function sprintf;

/**
 * SOL-342: a percentage discount typed through the form before this fix was stored in
 * hundredths (15 % became 1500). This migration canonicalises those rows to whole percent
 * without changing the discount any existing document already applies.
 *
 * SOL-348: the canonicalisation is total-preserving precisely because it makes the stored
 * value equal the applied percent for every row — which also erases the only evidence of
 * which `0 < v <= 100` rows came from the hundredths convention. `postUp()` therefore
 * captures those rows into `ambiguous_discount_units` before it canonicalises them.
 */
final class DiscountPercentageCanonicalisationMigrationTest extends TestCase
{
    private const array TABLES = ['invoices', 'recurring_invoices', 'quotes'];

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->connection->executeStatement(
            'CREATE TABLE ambiguous_discount_units ('
            . 'id VARCHAR(32) NOT NULL PRIMARY KEY, '
            . 'company_id VARCHAR(32) NOT NULL, '
            . 'document_type VARCHAR(20) NOT NULL, '
            . 'captured_percentage FLOAT NOT NULL, '
            . 'captured_at DATETIME NOT NULL'
            . ')'
        );

        foreach (self::TABLES as $table) {
            $this->connection->executeStatement(sprintf(
                'CREATE TABLE %s ('
                . 'id VARCHAR(32) NOT NULL PRIMARY KEY, '
                . 'company_id VARCHAR(32) NOT NULL, '
                . 'discount_type VARCHAR(255) NULL, '
                . 'discount_value_percentage FLOAT NULL'
                . ')',
                $table
            ));
        }
    }

    public function testUpCreatesTheCaptureTable(): void
    {
        $schema = new Schema();

        $this->migration()->up($schema);

        $table = $schema->getTable('ambiguous_discount_units');

        self::assertTrue($table->getColumn('id')->getNotnull());
        self::assertTrue($table->getColumn('company_id')->getNotnull());
        self::assertSame(20, $table->getColumn('document_type')->getLength());
        self::assertTrue($table->getColumn('captured_percentage')->getNotnull());
        self::assertTrue($table->getColumn('captured_at')->getNotnull());
        self::assertSame(['id'], $table->getPrimaryKey()?->getColumns());
        self::assertTrue($table->columnsAreIndexed(['company_id']));
    }

    public function testDownDropsTheCaptureTableFromSchema(): void
    {
        $schema = new Schema();
        $migration = $this->migration();

        $migration->up($schema);
        $migration->down($schema);

        self::assertFalse($schema->hasTable('ambiguous_discount_units'));
    }

    /**
     * @return iterable<string, array{float|null, float|null}>
     */
    public static function storedValues(): iterable
    {
        yield 'hundredths above the boundary is canonicalised' => [1500.0, 15.0];
        yield 'hundredths just above the boundary is canonicalised' => [101.0, 1.01];
        yield 'at the boundary is captured, then left alone' => [100.0, 100.0];
        yield 'below the boundary is captured, then left alone' => [50.0, 50.0];
        yield 'a typed 0.5 % is captured, then left alone' => [0.5, 0.5];
        yield 'zero is left alone, and not captured' => [0.0, 0.0];
        yield 'null is left alone, and not captured' => [null, null];
    }

    #[DataProvider('storedValues')]
    public function testPostUpCanonicalisesOnlyValuesAboveTheBoundary(?float $stored, ?float $expected): void
    {
        // Document ids are ULIDs in production and do not collide across the three
        // source tables (SOL-348). A per-table id here keeps that assumption true for
        // this fixture, since all three rows land in the one shared capture table.
        foreach (self::TABLES as $table) {
            $this->connection->insert($table, [
                'id' => $table . '-row-1',
                'company_id' => 'company-1',
                'discount_type' => 'percentage',
                'discount_value_percentage' => $stored,
            ]);
        }

        $this->migration()->postUp($this->liveSchema());

        foreach (self::TABLES as $table) {
            $value = $this->connection->fetchOne(
                sprintf('SELECT discount_value_percentage FROM %s WHERE id = ?', $table),
                [$table . '-row-1']
            );

            self::assertSame($expected, $value === null ? null : (float) $value, $table);
        }
    }

    /**
     * The ambiguous band — `0 < v <= 100` — is exactly the population SOL-348 captures.
     * `1500` and `101` are unambiguously hundredths (canonicalised, not captured); `0`
     * and `NULL` carry no discount at all.
     */
    public function testPostUpCapturesExactlyTheAmbiguousBand(): void
    {
        foreach (self::TABLES as $table) {
            $rows = ['keep-1500' => 1500.0, 'keep-101' => 101.0, 'capture-100' => 100.0, 'capture-50' => 50.0, 'capture-half' => 0.5, 'skip-zero' => 0.0, 'skip-null' => null];

            foreach ($rows as $suffix => $value) {
                $this->connection->insert($table, [
                    'id' => $table . '-' . $suffix,
                    'company_id' => 'company-1',
                    'discount_type' => 'percentage',
                    'discount_value_percentage' => $value,
                ]);
            }
        }

        $this->migration()->postUp($this->liveSchema());

        $captured = $this->connection->fetchAllAssociative(
            'SELECT id, captured_percentage FROM ambiguous_discount_units ORDER BY id'
        );

        self::assertCount(3 * count(self::TABLES), $captured);

        $byId = [];
        foreach ($captured as $row) {
            $byId[$row['id']] = (float) $row['captured_percentage'];
        }

        foreach (self::TABLES as $table) {
            self::assertSame(100.0, $byId[$table . '-capture-100']);
            self::assertSame(50.0, $byId[$table . '-capture-50']);
            self::assertSame(0.5, $byId[$table . '-capture-half']);
            self::assertArrayNotHasKey($table . '-keep-1500', $byId);
            self::assertArrayNotHasKey($table . '-keep-101', $byId);
            self::assertArrayNotHasKey($table . '-skip-zero', $byId);
            self::assertArrayNotHasKey($table . '-skip-null', $byId);
        }
    }

    public function testPostUpCapturesTheSourceDocumentsCompanyId(): void
    {
        foreach (self::TABLES as $table) {
            $this->connection->insert($table, [
                'id' => $table . '-company-a',
                'company_id' => 'company-a',
                'discount_type' => 'percentage',
                'discount_value_percentage' => 50.0,
            ]);
            $this->connection->insert($table, [
                'id' => $table . '-company-b',
                'company_id' => 'company-b',
                'discount_type' => 'percentage',
                'discount_value_percentage' => 60.0,
            ]);
        }

        $this->migration()->postUp($this->liveSchema());

        foreach (self::TABLES as $table) {
            self::assertSame(
                'company-a',
                $this->connection->fetchOne('SELECT company_id FROM ambiguous_discount_units WHERE id = ?', [$table . '-company-a']),
                $table
            );
            self::assertSame(
                'company-b',
                $this->connection->fetchOne('SELECT company_id FROM ambiguous_discount_units WHERE id = ?', [$table . '-company-b']),
                $table
            );
        }
    }

    public function testPostUpASecondRunFailsOnThePrimaryKeyRatherThanDuplicating(): void
    {
        $this->connection->insert('invoices', [
            'id' => 'row-1',
            'company_id' => 'company-1',
            'discount_type' => 'percentage',
            'discount_value_percentage' => 50.0,
        ]);

        $migration = $this->migration();
        $migration->postUp($this->liveSchema());

        self::expectException(UniqueConstraintViolationException::class);

        $migration->postUp($this->liveSchema());
    }

    /**
     * A money discount never went through the hundredths convention this migration
     * repairs, so a stored value above 100 for `TYPE_MONEY` must not move, and must not
     * be captured either.
     */
    public function testPostUpLeavesMoneyDiscountsAlone(): void
    {
        foreach (self::TABLES as $table) {
            $this->connection->insert($table, [
                'id' => $table . '-row-1',
                'company_id' => 'company-1',
                'discount_type' => 'money',
                'discount_value_percentage' => 1500.0,
            ]);
        }

        $this->migration()->postUp($this->liveSchema());

        foreach (self::TABLES as $table) {
            $value = $this->connection->fetchOne(
                sprintf('SELECT discount_value_percentage FROM %s WHERE id = ?', $table),
                [$table . '-row-1']
            );

            self::assertSame(1500.0, (float) $value, $table);
        }

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ambiguous_discount_units'));
    }

    public function testDownDropsTheCaptureTableAndLeavesTheCanonicalisationAlone(): void
    {
        foreach (self::TABLES as $table) {
            $this->connection->insert($table, [
                'id' => $table . '-row-1',
                'company_id' => 'company-1',
                'discount_type' => 'percentage',
                'discount_value_percentage' => 15.0,
            ]);
        }

        $migration = $this->migration();
        $schema = $this->liveSchema();
        $migration->postUp($schema);
        $migration->down($schema);

        self::assertFalse($schema->hasTable('ambiguous_discount_units'));

        foreach (self::TABLES as $table) {
            $value = $this->connection->fetchOne(
                sprintf('SELECT discount_value_percentage FROM %s WHERE id = ?', $table),
                [$table . '-row-1']
            );

            self::assertSame(15.0, (float) $value, $table);
        }
    }

    private function liveSchema(): Schema
    {
        return $this->connection->createSchemaManager()->introspectSchema();
    }

    private function migration(): Version30100_13
    {
        return new Version30100_13($this->connection, new NullLogger());
    }
}
