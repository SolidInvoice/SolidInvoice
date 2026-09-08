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
     * The columns created with the legacy serialize-backed `array` type, as table => column.
     *
     * @var array<string, string>
     */
    private const array LEGACY_ARRAY_COLUMNS = [
        'users' => 'roles',
        'api_token_history' => 'requestData',
        'payment_methods' => 'config',
    ];

    private const int PAGE_SIZE = 500;

    public function getDescription(): string
    {
        return 'Store the legacy serialized array columns as JSON, and widen totp_secret to 64 characters';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        // MySQL aborts the ALTER in up() on the first row that does not parse as JSON
        // (SQLSTATE 22032), and Postgres' cast is just as strict, so the values have to be
        // converted before the column changes rather than after it.
        foreach (self::LEGACY_ARRAY_COLUMNS as $table => $column) {
            $this->connection->executeStatement(
                sprintf('UPDATE %s SET %s = ? WHERE %s IS NULL', $table, $column, $column),
                ['[]'],
            );

            $this->rewrite($table, $column, static function (string $value): ?string {
                if (json_validate($value)) {
                    return null;
                }

                $unserialized = @unserialize($value, ['allowed_classes' => false]);

                return json_encode(is_array($unserialized) ? $unserialized : [], JSON_THROW_ON_ERROR);
            });
        }
    }

    public function up(Schema $schema): void
    {
        foreach (self::LEGACY_ARRAY_COLUMNS as $table => $column) {
            if ($this->platform instanceof PostgreSQLPlatform) {
                // Postgres has no assignment cast from text to json, and the bare
                // `ALTER <column> TYPE JSON` that DBAL emits is rejected outright. Written out
                // here, and left out of the schema below so that statement is never generated.
                $this->addSql(sprintf('ALTER TABLE %s ALTER %s TYPE JSON USING %s::json', $table, $column, $column));

                continue;
            }

            $schema->getTable($table)
                ->getColumn($column)
                ->setType(Type::getType(Types::JSON))
                ->setLength(null);
        }

        // Nullable behind a non-nullable array property; preUp has replaced the nulls.
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
        // Serialized values are not valid JSON, so the data can only be restored once down()
        // has turned the columns back into text.
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
     * Rewrites every value in a column, a page of rows at a time, grouping each page by its new
     * value. Grouping in PHP rather than with SELECT DISTINCT keeps the match byte exact: MySQL's
     * case-insensitive collations would fold two payloads differing only in case into one and hand
     * both rows the same replacement.
     *
     * @param callable(string): ?string $convert returns null for values to leave alone
     *
     * @throws Exception
     */
    private function rewrite(string $table, string $column, callable $convert): void
    {
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
                // Ids bind as strings on every platform: BINARY(16) bytes on MySQL and SQLite,
                // RFC 4122 text in a UUID column on Postgres.
                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET %s = ? WHERE id IN (?)', $table, $column),
                    [(string) $value, $ids],
                    [ParameterType::STRING, ArrayParameterType::STRING],
                );
            }
        }
    }
}
