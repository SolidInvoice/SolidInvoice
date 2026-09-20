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
     * `invoice_lines` appears twice: it is a single-table hierarchy, so invoice lines and
     * recurring invoice lines share it but must be numbered within their own parent.
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
     * came back in before there was a column to make it explicit. Numbering from it keeps an
     * existing invoice rendering exactly as it did.
     *
     * In PHP rather than one `UPDATE … ROW_NUMBER()`: the supported platforms spell an
     * update-from-a-derived-table three different ways, and MySQL will not read the table it
     * is updating in a correlated subquery. Ids are grouped by the position they are getting,
     * so a page collapses into a handful of `IN (…)` updates.
     *
     * @throws Exception
     */
    private function numberLinesByOwner(string $table, string $ownerColumn): void
    {
        $lastOwner = null;
        $lastId = null;
        $position = 0;

        while (true) {
            $page = $this->connection->createQueryBuilder();
            $expr = $page->expr();

            $page
                ->select('id', sprintf('%s AS owner', $ownerColumn))
                ->from($table)
                ->where($expr->isNotNull($ownerColumn))
                ->orderBy($ownerColumn, 'ASC')
                ->addOrderBy('id', 'ASC')
                ->setMaxResults(self::PAGE_SIZE);

            if ($lastId !== null) {
                $page
                    ->andWhere($expr->or(
                        $expr->gt($ownerColumn, ':lastOwner'),
                        $expr->and($expr->eq($ownerColumn, ':lastOwner'), $expr->gt('id', ':lastId')),
                    ))
                    ->setParameter('lastOwner', $lastOwner)
                    ->setParameter('lastId', $lastId);
            }

            $rows = $page->executeQuery()->fetchAllAssociative();

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
                $update = $this->connection->createQueryBuilder();

                $update
                    ->update($table)
                    ->set($this->platform->quoteSingleIdentifier('position'), ':position')
                    ->where($update->expr()->in('id', ':ids'))
                    ->setParameter('position', $value, ParameterType::INTEGER)
                    // Ids bind as strings on every platform: BINARY(16) bytes on MySQL and
                    // SQLite, RFC 4122 text in a UUID column on Postgres.
                    ->setParameter('ids', $ids, ArrayParameterType::STRING)
                    ->executeStatement();
            }
        }
    }
}
