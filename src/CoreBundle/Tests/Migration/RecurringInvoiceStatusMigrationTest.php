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
use DoctrineMigrations\Version30100_11;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;

final class RecurringInvoiceStatusMigrationTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->connection->executeStatement(
            'CREATE TABLE invoices (id VARCHAR(32) NOT NULL PRIMARY KEY, status VARCHAR(25) NOT NULL, archived BOOLEAN NULL)'
        );
    }

    public function testUpArchivesALegacyRecurringRowOnBothColumns(): void
    {
        $this->connection->insert('invoices', ['id' => 'invoice-1', 'status' => 'recurring', 'archived' => null]);

        $this->runMigration();

        $row = $this->connection->fetchAssociative('SELECT status, archived FROM invoices WHERE id = ?', ['invoice-1']);

        self::assertIsArray($row);
        self::assertSame('archived', $row['status']);
        self::assertSame(1, (int) $row['archived']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function untouchedStatuses(): iterable
    {
        foreach (['pending', 'paid', 'draft', 'cancelled', 'archived'] as $status) {
            yield $status => [$status];
        }
    }

    #[DataProvider('untouchedStatuses')]
    public function testUpLeavesEveryOtherStatusAlone(string $status): void
    {
        $this->connection->insert('invoices', ['id' => 'invoice-1', 'status' => $status, 'archived' => null]);

        $this->runMigration();

        self::assertSame(
            $status,
            $this->connection->fetchOne('SELECT status FROM invoices WHERE id = ?', ['invoice-1'])
        );
    }

    public function testRunningTheMigrationTwiceChangesNothingTheSecondTime(): void
    {
        $this->connection->insert('invoices', ['id' => 'invoice-1', 'status' => 'recurring', 'archived' => null]);

        $this->runMigration();
        $this->runMigration();

        $row = $this->connection->fetchAssociative('SELECT status, archived FROM invoices WHERE id = ?', ['invoice-1']);

        self::assertIsArray($row);
        self::assertSame('archived', $row['status']);
        self::assertSame(1, (int) $row['archived']);
    }

    public function testTheArchivedRowIsExcludedByTheArchivableFilterPredicate(): void
    {
        $this->connection->insert('invoices', ['id' => 'invoice-1', 'status' => 'recurring', 'archived' => null]);

        $this->runMigration();

        // The predicate ArchivableFilter::addFilterConstraint() applies to every
        // archivable entity. Setting only `status` would leave `archived` NULL and this
        // count would still be 1 — the row would keep showing in Active Invoices.
        $visible = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM invoices WHERE id = ? AND (archived IS NULL OR archived = 0)',
            ['invoice-1'],
        );

        self::assertSame(0, $visible);
    }

    public function testUpOnADatabaseWithNoMatchingRowsSucceedsAndReportsZero(): void
    {
        $this->connection->insert('invoices', ['id' => 'invoice-1', 'status' => 'paid', 'archived' => null]);

        $logger = new class() extends AbstractLogger {
            public ?string $lastMessage = null;

            /**
             * @param array<mixed> $context
             */
            public function log($level, string | Stringable $message, array $context = []): void
            {
                $this->lastMessage = (string) $message;
            }
        };

        $this->runMigration($logger);

        self::assertSame(
            'paid',
            $this->connection->fetchOne('SELECT status FROM invoices WHERE id = ?', ['invoice-1'])
        );
        self::assertNotNull($logger->lastMessage);
        self::assertStringContainsString('No invoices', (string) $logger->lastMessage);
    }

    private function runMigration(?AbstractLogger $logger = null): void
    {
        $migration = $this->migration($logger);
        $migration->preUp(new Schema());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }

    private function migration(?AbstractLogger $logger = null): Version30100_11
    {
        return new Version30100_11($this->connection, $logger ?? new NullLogger());
    }
}
