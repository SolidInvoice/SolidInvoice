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
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Doctrine\Migrations\DryRunAwareMigration;
use function explode;
use function mb_strlen;
use function mb_substr;
use function rtrim;
use function str_replace;
use function trim;

final class Version30100_6 extends AbstractMigration
{
    use DryRunAwareMigration;

    private const array LINE_TABLES = ['invoice_lines', 'quote_lines'];

    private const int NAME_MAX_LENGTH = 255;

    private const string ELLIPSIS = '…';

    private const int PAGE_SIZE = 500;

    public function getDescription(): string
    {
        return 'Give invoice, recurring invoice and quote lines a name, backfilled from their description';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (self::LINE_TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn('name')) {
                $table->addColumn('name', Types::STRING, [
                    'length' => self::NAME_MAX_LENGTH,
                    'notnull' => true,
                    'default' => '',
                ]);
            }

            // A line that is only a name no longer needs a description, and the backfill
            // below is what turns most existing lines into exactly that.
            $table->getColumn('description')->setNotnull(false);
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        foreach (self::LINE_TABLES as $table) {
            $this->backfillNames($table);
        }
    }

    /**
     * Guarded on {@see DryRunAwareMigration::isDryRun()}: this hook runs before `down()` would
     * drop the `name` column it reads from, so the column exists whether this is a real revert
     * or a `--dry-run` one — there is no post-`down()` state to check instead. Without the
     * guard, a dry-run down would move every line's `name` back into `description` for real,
     * on a live database, while `down()` itself makes no real change under a dry run.
     *
     * @throws Exception
     */
    public function preDown(Schema $schema): void
    {
        if ($this->isDryRun()) {
            return;
        }

        // A description the backfill moved wholesale into the name has to come back before
        // the column is `NOT NULL` again, or down() fails on the first line it moved.
        foreach (self::LINE_TABLES as $table) {
            $this->connection->createQueryBuilder()
                ->update($table)
                ->set('description', $this->platform->quoteSingleIdentifier('name'))
                ->where('description IS NULL')
                ->executeStatement();
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::LINE_TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('name')) {
                $table->dropColumn('name');
            }

            $table->getColumn('description')->setNotnull(true);
        }
    }

    /**
     * Names every existing line after the first line of its description.
     *
     * The rule is spelled out here rather than called out of
     * {@see \SolidInvoice\CoreBundle\Billing\LineName}, because a migration has to keep
     * producing the result it produced the day it was written. If the runtime rule changes,
     * the lines this pass already named must not change with it.
     *
     * Runs in PHP rather than as one UPDATE: finding the first line of a string is spelled
     * three different ways across the four supported platforms. Rows are read a page at a
     * time, and the page that needs no truncation — which is nearly all of them, since a
     * short one-line description becomes the whole name — collapses into a single statement.
     *
     * @throws Exception
     */
    private function backfillNames(string $table): void
    {
        $nameColumn = $this->platform->quoteSingleIdentifier('name');
        $lastId = null;

        while (true) {
            $query = $this->connection->createQueryBuilder()
                ->select('id', 'description')
                ->from($table)
                ->where($nameColumn . ' = :empty')
                ->setParameter('empty', '')
                ->orderBy('id', 'ASC')
                ->setMaxResults(self::PAGE_SIZE);

            if ($lastId !== null) {
                $query->andWhere('id > :lastId')
                    ->setParameter('lastId', $lastId);
            }

            $rows = $query->executeQuery()->fetchAllAssociative();

            if ($rows === []) {
                return;
            }

            /** @var list<string> $wholeDescriptions */
            $wholeDescriptions = [];

            foreach ($rows as $row) {
                // Ids bind as strings on every platform: BINARY(16) bytes on MySQL and
                // SQLite, RFC 4122 text in a UUID column on Postgres.
                $lastId = (string) $row['id'];
                $description = (string) ($row['description'] ?? '');
                $name = $this->nameFor($description);

                if ($name === '') {
                    // Nothing to name the line after. `description` was NOT NULL and
                    // validated as non-blank until now, so this is a row that predates
                    // that or was written around it.
                    continue;
                }

                if ($description === $name) {
                    $wholeDescriptions[] = $lastId;

                    continue;
                }

                $this->connection->update($table, [$nameColumn => $name], ['id' => $lastId]);
            }

            if ($wholeDescriptions !== []) {
                $this->connection->createQueryBuilder()
                    ->update($table)
                    ->set($nameColumn, 'description')
                    ->set('description', 'NULL')
                    ->where('id IN (:ids)')
                    ->setParameter('ids', $wholeDescriptions, ArrayParameterType::STRING)
                    ->executeStatement();
            }
        }
    }

    private function nameFor(string $description): string
    {
        // Lone CR as well as CRLF: a description stored by an older client can use either,
        // and one that arrives as a single `\r`-separated run would otherwise be read as one
        // very long line and truncated in the middle of it.
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $description)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (mb_strlen($line) <= self::NAME_MAX_LENGTH) {
                return $line;
            }

            return rtrim(mb_substr($line, 0, self::NAME_MAX_LENGTH - mb_strlen(self::ELLIPSIS))) . self::ELLIPSIS;
        }

        return '';
    }
}
