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

namespace DoctrineMigrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use function sprintf;

final class Version30100_5 extends AbstractMigration
{
    /**
     * Every line collection that gains a position, as table => owning foreign key.
     *
     * `invoice_lines` is listed twice because it is a single-table hierarchy: an invoice line
     * and a recurring invoice line share the table but belong to different owners, so each
     * side has to be numbered within its own parent.
     *
     * @var list<array{string, string}>
     */
    private const array LINE_OWNERS = [
        ['invoice_lines', 'invoice_id'],
        // camelCase, and deliberately never quoted below: DBAL created it unquoted, so
        // Postgres folded it to `recurringinvoice_id` while MySQL and SQLite kept the case.
        // Bare is the one spelling that resolves on all four platforms.
        ['invoice_lines', 'recurringInvoice_id'],
        ['quote_lines', 'quote_id'],
    ];

    private const int PAGE_SIZE = 500;

    public function getDescription(): string
    {
        return 'Give invoice, recurring invoice and quote lines an explicit position, backfilled from their existing order';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (['invoice_lines', 'quote_lines'] as $tableName) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn('position')) {
                $table->addColumn('position', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        // Every existing line comes out of up() at position 0. Until they are numbered, the
        // `ORDER BY position` the entities now carry would leave the order to the database,
        // which is exactly the accident this column exists to remove.
        foreach (self::LINE_OWNERS as [$table, $ownerColumn]) {
            $this->numberLinesByOwner($table, $ownerColumn);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['invoice_lines', 'quote_lines'] as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('position')) {
                $table->dropColumn('position');
            }
        }
    }

    /**
     * Numbers each owner's lines `0..n-1` in primary key order.
     *
     * Ids are ULIDs, so ascending id is the insertion order — the order these lines already
     * came back in, in practice, before there was a column to make it explicit. Numbering from
     * it is what keeps an existing invoice rendering exactly as it did.
     *
     * The pass runs in PHP rather than as one `UPDATE … ROW_NUMBER()`, because the four
     * supported platforms spell an update-from-a-derived-table three different ways and MySQL
     * will not read the table it is updating in a correlated subquery. Rows are read a page at
     * a time, and the ids are grouped by the position they are getting: an invoice has a
     * handful of lines, so a page collapses into a handful of `IN (…)` updates.
     *
     * @throws Exception
     */
    private function numberLinesByOwner(string $table, string $ownerColumn): void
    {
        $lastOwner = null;
        $lastId = null;
        $position = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT id, %1$s AS owner FROM %2$s WHERE %1$s IS NOT NULL%3$s ORDER BY %1$s ASC, id ASC LIMIT %4$d',
                    $ownerColumn,
                    $table,
                    $lastId === null ? '' : sprintf(' AND (%1$s > ? OR (%1$s = ? AND id > ?))', $ownerColumn),
                    self::PAGE_SIZE,
                ),
                $lastId === null ? [] : [$lastOwner, $lastOwner, $lastId],
            );

            if ($rows === []) {
                return;
            }

            /** @var array<int, list<string>> $idsByPosition */
            $idsByPosition = [];

            foreach ($rows as $row) {
                // The cursor orders by owner then id, so a change of owner restarts the count —
                // including across a page boundary, which is why the owner is carried over.
                if ($row['owner'] !== $lastOwner) {
                    $lastOwner = $row['owner'];
                    $position = 0;
                }

                $lastId = $row['id'];
                $idsByPosition[$position][] = $row['id'];
                ++$position;
            }

            foreach ($idsByPosition as $value => $ids) {
                // Ids bind as strings on every platform: BINARY(16) bytes on MySQL and SQLite,
                // RFC 4122 text in a UUID column on Postgres.
                $this->connection->executeStatement(
                    sprintf(
                        'UPDATE %s SET %s = ? WHERE id IN (?)',
                        $table,
                        $this->platform->quoteSingleIdentifier('position'),
                    ),
                    [$value, $ids],
                    [ParameterType::INTEGER, ArrayParameterType::STRING],
                );
            }
        }
    }
}
