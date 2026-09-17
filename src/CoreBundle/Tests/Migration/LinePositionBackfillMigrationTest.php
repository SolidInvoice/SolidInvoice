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

use const STR_PAD_LEFT;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version30100_5;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function array_column;
use function array_fill;
use function array_merge;
use function count;
use function implode;
use function range;
use function sprintf;
use function str_pad;

/**
 * `postUp()` is the only thing standing between an upgrade and a reshuffled invoice: it is
 * where the order lines already had becomes the order the new column records.
 *
 * @see Version30100_5
 */
final class LinePositionBackfillMigrationTest extends TestCase
{
    /**
     * Two owners of this many lines is more than the migration's page size, so the second
     * owner's lines straddle a page boundary — and the count has to be carried across it
     * rather than restarting at the top of the next page.
     */
    private const int LINES_PER_OWNER = 400;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->connection->executeStatement(
            'CREATE TABLE invoice_lines (id VARCHAR(26) NOT NULL PRIMARY KEY, invoice_id VARCHAR(26) DEFAULT NULL, recurringInvoice_id VARCHAR(26) DEFAULT NULL, position INTEGER NOT NULL DEFAULT 0)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE quote_lines (id VARCHAR(26) NOT NULL PRIMARY KEY, quote_id VARCHAR(26) DEFAULT NULL, position INTEGER NOT NULL DEFAULT 0)'
        );
    }

    /**
     * @throws Exception
     */
    public function testPostUpNumbersEachOwnersLinesFromZeroInIdOrder(): void
    {
        $this->insertLines('invoice_lines', 'invoice_id', ['invoice-a', 'invoice-b']);
        $this->insertLines('quote_lines', 'quote_id', ['quote-a']);

        $this->migration()->postUp(new Schema());

        foreach (['invoice-a', 'invoice-b'] as $owner) {
            self::assertSame(
                range(0, self::LINES_PER_OWNER - 1),
                $this->positionsOf('invoice_lines', 'invoice_id', $owner),
                sprintf('%s should be numbered 0..n-1 in id order', $owner),
            );
        }

        self::assertSame(
            range(0, self::LINES_PER_OWNER - 1),
            $this->positionsOf('quote_lines', 'quote_id', 'quote-a'),
        );
    }

    /**
     * Invoice lines and recurring invoice lines share a table, so each side has to be counted
     * within its own parent — and the pass over one owning column has to leave the other's
     * rows, whose column is null, alone.
     *
     * @throws Exception
     */
    public function testPostUpNumbersRecurringInvoiceLinesSeparatelyFromInvoiceLines(): void
    {
        $this->insertLines('invoice_lines', 'invoice_id', ['invoice-a']);
        $this->insertLines('invoice_lines', 'recurringInvoice_id', ['recurring-a']);

        $this->migration()->postUp(new Schema());

        self::assertSame(
            range(0, self::LINES_PER_OWNER - 1),
            $this->positionsOf('invoice_lines', 'invoice_id', 'invoice-a'),
        );

        self::assertSame(
            range(0, self::LINES_PER_OWNER - 1),
            $this->positionsOf('invoice_lines', 'recurringInvoice_id', 'recurring-a'),
        );
    }

    /**
     * Ids are inserted out of order on purpose: the backfill reads them back sorted, and a
     * fixture that was already sorted would pass even if it did not.
     *
     * @param list<string> $owners
     *
     * @throws Exception
     */
    private function insertLines(string $table, string $ownerColumn, array $owners): void
    {
        $rows = [];
        $parameters = [];

        foreach ($owners as $owner) {
            for ($index = 0; $index < self::LINES_PER_OWNER; ++$index) {
                $rows[] = [$this->id($owner, self::LINES_PER_OWNER - 1 - $index), $owner];
            }
        }

        foreach ($rows as $row) {
            $parameters = array_merge($parameters, $row);
        }

        $this->connection->executeStatement(
            sprintf(
                'INSERT INTO %s (id, %s) VALUES %s',
                $table,
                $ownerColumn,
                implode(', ', array_fill(0, count($rows), '(?, ?)')),
            ),
            $parameters,
        );
    }

    /**
     * @return list<int>
     *
     * @throws Exception
     */
    private function positionsOf(string $table, string $ownerColumn, string $owner): array
    {
        return array_column(
            $this->connection->fetchAllAssociative(
                sprintf('SELECT position FROM %s WHERE %s = ? ORDER BY id ASC', $table, $ownerColumn),
                [$owner],
            ),
            'position',
        );
    }

    /**
     * Sortable, so ascending id is the order the lines were created in — which is the property
     * the backfill leans on when it reads ULIDs out of a real database.
     */
    private function id(string $owner, int $index): string
    {
        return sprintf('%s-%s', $owner, str_pad((string) $index, 6, '0', STR_PAD_LEFT));
    }

    private function migration(): Version30100_5
    {
        return new Version30100_5($this->connection, new NullLogger());
    }
}
