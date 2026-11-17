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

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use function date_default_timezone_get;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * Since 3.1, `SolidWorxPlatformBundle` overrides `datetime_immutable` with `UTCDateTimeType`, a
 * `timestamptz` type that only parses values carrying an offset. In 3.0.x that override was
 * commented out, so on PostgreSQL every `datetime_immutable` column 3.0.x created (and every
 * `created`/`updated`/`completed` column, which the early migrations declared as plain
 * `datetime`) is `TIMESTAMP(0) WITHOUT TIME ZONE`. Reading any of them after the upgrade throws a
 * ConversionException (#3132).
 *
 * MySQL and MariaDB have no `timestamptz`; both types map to DATETIME there, so this only
 * touches PostgreSQL. Each column is converted only while it is still `timestamp`, so a fresh
 * install, whose schema already has `timestamptz`, is a no-op.
 *
 * Doctrine's own diff would emit the ALTER without a USING clause, which reads every stored value
 * in the session's TimeZone and silently shifts it on a server not running UTC. 3.0.x wrote these
 * values with DateTimeImmutableType, i.e. as wall-clock time in PHP's default timezone, so that is
 * the zone the USING clause reads them in. That is UTC unless date.timezone was changed.
 */
final class Version30104_1 extends AbstractMigration
{
    /**
     * TimeStampable's columns, on every table that has them.
     */
    private const array TIMESTAMPABLE_COLUMNS = ['created', 'updated'];

    /**
     * Every other column mapped as `datetime_immutable`, keyed by table. Lower-cased, because
     * the camelCase names were created unquoted and PostgreSQL folded them.
     */
    private const array COLUMNS = [
        'export_jobs' => ['created_at', 'completed_at'],
        'invoice_reminders' => ['sent_at'],
        'invoice_tax' => ['snapshotted_at'],
        'line_tax' => ['snapshotted_at'],
        'mcp_access_token' => ['expires_at', 'last_used_at'],
        'mcp_oauth_auth_code' => ['expires_at'],
        'mcp_refresh_token' => ['expires_at'],
        'payments' => ['completed'],
        'reset_password_request' => ['requestedat', 'expiresat'],
        'user_invitations' => ['expiresat', 'remindersentat'],
    ];

    public function getDescription(): string
    {
        return 'Convert the datetime_immutable columns 3.0.x created as timestamp without time zone to timestamptz on PostgreSQL, keeping every stored instant';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if (! $this->platform instanceof PostgreSQLPlatform) {
            return;
        }

        $timezone = $this->connection->quote(date_default_timezone_get());

        foreach ($schema->getTables() as $table) {
            $tableName = strtolower($table->getObjectName()->getUnqualifiedName()->getValue());
            $candidates = [...self::TIMESTAMPABLE_COLUMNS, ...(self::COLUMNS[$tableName] ?? [])];

            foreach ($table->getColumns() as $column) {
                $columnName = $column->getObjectName()->getIdentifier();

                if (! in_array(strtolower($columnName->getValue()), $candidates, true)) {
                    continue;
                }

                // PostgreSQL's reverse type mapping for `timestamp without time zone`.
                if ($column->getTypeName() !== Types::DATETIME_MUTABLE) {
                    continue;
                }

                $quotedColumn = $columnName->toSQL($this->platform);

                $this->addSql(sprintf(
                    'ALTER TABLE %s ALTER %s TYPE TIMESTAMP(0) WITH TIME ZONE USING %s AT TIME ZONE %s',
                    $table->getObjectName()->toSQL($this->platform),
                    $quotedColumn,
                    $quotedColumn,
                    $timezone,
                ));
            }
        }
    }

    /**
     * Deliberately a no-op: turning these columns back into `timestamp` would bring back the
     * ConversionException on every read, and on a fresh install they were never `timestamp`.
     */
    public function down(Schema $schema): void
    {
    }
}
