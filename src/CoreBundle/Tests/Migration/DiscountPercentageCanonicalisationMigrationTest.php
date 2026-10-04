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
use DoctrineMigrations\Version30100_13;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function sprintf;

/**
 * SOL-342: a percentage discount typed through the form before this fix was stored in
 * hundredths (15 % became 1500). This migration canonicalises those rows to whole percent
 * without changing the discount any existing document already applies.
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

        foreach (self::TABLES as $table) {
            $this->connection->executeStatement(sprintf(
                'CREATE TABLE %s (id VARCHAR(32) NOT NULL PRIMARY KEY, discount_type VARCHAR(255) NULL, discount_value_percentage FLOAT NULL)',
                $table
            ));
        }
    }

    /**
     * @return iterable<string, array{float|null, float|null}>
     */
    public static function storedValues(): iterable
    {
        yield 'hundredths above the boundary is canonicalised' => [1500.0, 15.0];
        yield 'hundredths just above the boundary is canonicalised' => [101.0, 1.01];
        yield 'at the boundary is left alone, same as a typed 100 %' => [100.0, 100.0];
        yield 'below the boundary is left alone — indistinguishable from an API 50 %' => [50.0, 50.0];
        yield 'zero is left alone' => [0.0, 0.0];
        yield 'null is left alone' => [null, null];
    }

    #[DataProvider('storedValues')]
    public function testPostUpCanonicalisesOnlyValuesAboveTheBoundary(?float $stored, ?float $expected): void
    {
        foreach (self::TABLES as $table) {
            $this->connection->insert($table, [
                'id' => 'row-1',
                'discount_type' => 'percentage',
                'discount_value_percentage' => $stored,
            ]);
        }

        $this->migration()->postUp(new Schema());

        foreach (self::TABLES as $table) {
            $value = $this->connection->fetchOne(
                sprintf('SELECT discount_value_percentage FROM %s WHERE id = ?', $table),
                ['row-1']
            );

            self::assertSame($expected, $value === null ? null : (float) $value, $table);
        }
    }

    /**
     * A money discount never went through the hundredths convention this migration
     * repairs, so a stored value above 100 for `TYPE_MONEY` must not move.
     */
    public function testPostUpLeavesMoneyDiscountsAlone(): void
    {
        foreach (self::TABLES as $table) {
            $this->connection->insert($table, [
                'id' => 'row-1',
                'discount_type' => 'money',
                'discount_value_percentage' => 1500.0,
            ]);
        }

        $this->migration()->postUp(new Schema());

        foreach (self::TABLES as $table) {
            $value = $this->connection->fetchOne(
                sprintf('SELECT discount_value_percentage FROM %s WHERE id = ?', $table),
                ['row-1']
            );

            self::assertSame(1500.0, (float) $value, $table);
        }
    }

    public function testDownIsADeliberateDataNoOp(): void
    {
        foreach (self::TABLES as $table) {
            $this->connection->insert($table, [
                'id' => 'row-1',
                'discount_type' => 'percentage',
                'discount_value_percentage' => 15.0,
            ]);
        }

        $migration = $this->migration();
        $migration->postUp(new Schema());
        $migration->down(new Schema());

        foreach (self::TABLES as $table) {
            $value = $this->connection->fetchOne(
                sprintf('SELECT discount_value_percentage FROM %s WHERE id = ?', $table),
                ['row-1']
            );

            self::assertSame(15.0, (float) $value, $table);
        }
    }

    private function migration(): Version30100_13
    {
        return new Version30100_13($this->connection, new NullLogger());
    }
}
