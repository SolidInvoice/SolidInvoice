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
use function explode;
use function mb_strlen;
use function mb_substr;
use function rtrim;
use function sprintf;
use function str_replace;
use function trim;

final class Version30100_6 extends AbstractMigration
{
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
     * @throws Exception
     */
    public function preDown(Schema $schema): void
    {
        // A description the backfill moved wholesale into the name has to come back before
        // the column is `NOT NULL` again, or down() fails on the first line it moved.
        foreach (self::LINE_TABLES as $table) {
            $this->connection->executeStatement(
                sprintf(
                    'UPDATE %s SET description = %s WHERE description IS NULL',
                    $table,
                    $this->platform->quoteSingleIdentifier('name'),
                )
            );
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
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT id, description FROM %s WHERE %s = \'\'%s ORDER BY id ASC LIMIT %d',
                    $table,
                    $nameColumn,
                    $lastId === null ? '' : ' AND id > ?',
                    self::PAGE_SIZE,
                ),
                $lastId === null ? [] : [$lastId],
            );

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

                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET %s = ? WHERE id = ?', $table, $nameColumn),
                    [$name, $lastId],
                );
            }

            if ($wholeDescriptions !== []) {
                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET %s = description, description = NULL WHERE id IN (?)', $table, $nameColumn),
                    [$wholeDescriptions],
                    [ArrayParameterType::STRING],
                );
            }
        }
    }

    private function nameFor(string $description): string
    {
        $firstLine = trim(explode("\n", str_replace("\r\n", "\n", $description), 2)[0]);

        if (mb_strlen($firstLine) <= self::NAME_MAX_LENGTH) {
            return $firstLine;
        }

        return rtrim(mb_substr($firstLine, 0, self::NAME_MAX_LENGTH - mb_strlen(self::ELLIPSIS))) . self::ELLIPSIS;
    }
}
