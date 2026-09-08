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

use const JSON_THROW_ON_ERROR;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use function is_array;
use function json_decode;
use function json_encode;
use function json_validate;
use function serialize;
use function sprintf;
use function unserialize;

final class Version30100_3 extends AbstractMigration
{
    /**
     * Every column the legacy serialize-backed `array` type created, as table => column. The type
     * is removed in this release, so all of them become real JSON columns.
     *
     * users.roles included: its mapping was moved to `json` without a migration, so every database
     * that has not been repaired by hand still holds serialized roles behind a mapping that cannot
     * read them.
     *
     * @var array<string, string>
     */
    private const array LEGACY_ARRAY_COLUMNS = [
        'users' => 'roles',
        'api_token_history' => 'requestData',
        'payment_methods' => 'config',
    ];

    /**
     * Rows read, and at most rows updated, per round trip. Bounds both the memory the rewrite
     * holds and the size of its IN () lists.
     */
    private const int PAGE_SIZE = 500;

    public function getDescription(): string
    {
        return 'Store the legacy serialized array columns as JSON, and widen totp_secret to 64 characters';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL, which leaves the migration's transaction
        // gone by the time it is committed. AbstractMySQLPlatform rather than MySQLPlatform:
        // MariaDBPlatform is a sibling of MySQLPlatform, not a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        // MySQL parses every row while it rewrites a column to JSON and aborts the whole ALTER on
        // the first serialized value (SQLSTATE 22032); Postgres' cast in up() is just as strict.
        // The columns therefore have to hold JSON before up() runs, not after it.
        foreach (self::LEGACY_ARRAY_COLUMNS as $table => $column) {
            // A null would come back out of a json column as null and blow up the typed array
            // property it maps to, where the legacy type handed back an empty array. Settle that
            // while the column is still text.
            $this->connection->executeStatement(
                sprintf('UPDATE %s SET %s = ? WHERE %s IS NULL', $table, $column, $column),
                ['[]'],
            );

            $this->rewrite($table, $column, static function (string $value): ?string {
                if (json_validate($value)) {
                    return null;
                }

                // These columns only ever held serialized arrays, so objects are never expected:
                // refuse to instantiate any, and fall back to an empty array for values too
                // corrupt to read, which is what makes an upgrade from a half-broken column
                // possible at all.
                $unserialized = @unserialize($value, ['allowed_classes' => false]);

                return json_encode(is_array($unserialized) ? $unserialized : [], JSON_THROW_ON_ERROR);
            });
        }
    }

    public function up(Schema $schema): void
    {
        foreach (self::LEGACY_ARRAY_COLUMNS as $table => $column) {
            if ($this->platform instanceof PostgreSQLPlatform) {
                // Postgres has no assignment cast from text to json, and DBAL emits a bare
                // `ALTER <column> TYPE JSON` that it rejects outright, so spell the cast out here
                // and leave the column alone in the schema so nothing emits that statement too.
                $this->addSql(sprintf('ALTER TABLE %s ALTER %s TYPE JSON USING %s::json', $table, $column, $column));

                continue;
            }

            $schema->getTable($table)
                ->getColumn($column)
                ->setType(Type::getType(Types::JSON))
                ->setLength(null);
        }

        // The property behind this column is a plain `array` with an empty default, and the legacy
        // type turned a null into `[]` on the way out. Doctrine's json type hands back the null
        // instead, so make the column say what the mapping has always assumed. preUp has already
        // replaced any null with `[]`.
        $schema->getTable('payment_methods')
            ->getColumn('config')
            ->setNotnull(true);

        $schema->getTable('users')
            ->getColumn('totp_secret')
            ->setLength(64);
    }

    public function down(Schema $schema): void
    {
        foreach (self::LEGACY_ARRAY_COLUMNS as $table => $column) {
            $schema->getTable($table)
                ->getColumn($column)
                ->setType(Type::getType(Types::TEXT));
        }

        $schema->getTable('payment_methods')
            ->getColumn('config')
            ->setNotnull(false);

        $schema->getTable('users')
            ->getColumn('totp_secret')
            ->setLength(45);
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        // The mirror of preUp: rolling back the schema without rolling back the data would leave
        // JSON sitting in columns that the restored code reads with unserialize(), which silently
        // reads as an empty array — payment gateway credentials and user roles included.
        // Serialized values are not valid JSON, so this can only run once the columns are text
        // again.
        foreach (self::LEGACY_ARRAY_COLUMNS as $table => $column) {
            $this->rewrite($table, $column, static function (string $value): ?string {
                if (! json_validate($value)) {
                    return null;
                }

                $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

                return serialize(is_array($decoded) ? $decoded : []);
            });
        }
    }

    /**
     * Rewrites every value in a column, grouping each page of rows by their new value so that the
     * handful of distinct payloads these columns hold cost a handful of statements per page.
     *
     * Grouping happens in PHP rather than with SELECT DISTINCT to keep the match byte exact:
     * MySQL's case-insensitive collations would fold two payloads that differ only in case into
     * one, and hand both rows the same replacement.
     *
     * @param callable(string): ?string $convert returns null for values to leave alone
     *
     * @throws Exception
     */
    private function rewrite(string $table, string $column, callable $convert): void
    {
        // Paged by id rather than read in one go: api_token_history grows with every API request,
        // and the ids of a table that size do not belong in memory all at once. Ordering by the
        // primary key makes the cursor stable, and each page is small enough to be its own IN ().
        $lastId = null;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT id, %s AS value FROM %s%s ORDER BY id ASC LIMIT %d',
                    $column,
                    $table,
                    $lastId === null ? '' : ' WHERE id > ?',
                    self::PAGE_SIZE,
                ),
                $lastId === null ? [] : [$lastId],
            );

            if ($rows === []) {
                return;
            }

            /** @var array<string, list<string>> $rowIds */
            $rowIds = [];

            foreach ($rows as $row) {
                $lastId = $row['id'];
                $converted = $convert((string) $row['value']);

                if ($converted === null) {
                    continue;
                }

                $rowIds[$converted][] = $row['id'];
            }

            foreach ($rowIds as $value => $ids) {
                // Ids bind as strings on every platform: raw BINARY(16) bytes on MySQL, the same
                // bytes with TEXT storage class on SQLite, and the ULID rendered as RFC 4122 text
                // in a UUID column on Postgres, which is what Symfony's UlidType writes there.
                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET %s = ? WHERE id IN (?)', $table, $column),
                    [(string) $value, $ids],
                    [ParameterType::STRING, ArrayParameterType::STRING],
                );
            }
        }
    }
}
