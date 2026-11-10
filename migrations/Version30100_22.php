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
use function sprintf;

/**
 * `SolidWorxPlatformBundle` overrides `Types::DATETIME_IMMUTABLE` to `UTCDateTimeType`
 * (a `timestamptz` type that always reads and writes UTC), so every column mapped with
 * that type - `created`/`updated` from {@see \SolidInvoice\CoreBundle\Traits\Entity\TimeStampable}
 * plus {@see \SolidInvoice\PaymentBundle\Entity\Payment::$completed} - is `TIMESTAMP(0) WITH
 * TIME ZONE` in the mapping. A replayed PostgreSQL database still has the `TIMESTAMP(0)
 * WITHOUT TIME ZONE` columns the original migrations created before that override existed.
 *
 * `created` and `updated` are declared nowhere else in the mapping, and `completed` is
 * declared only on `Payment`, so finding every table with one of those columns still typed
 * `timestamp` (PostgreSQL's reverse type mapping for a `timestamp without time zone` column,
 * {@see \Doctrine\DBAL\Platforms\PostgreSQLPlatform::initializeDoctrineTypeMappings()}) picks
 * up exactly the right set without a fixed table list - the next entity to use
 * `TimeStampable` is covered by this same logic, not a manual update to this migration.
 *
 * The one line that matters: Doctrine's own schema-diff would emit `ALTER TABLE x ALTER
 * created TYPE TIMESTAMP(0) WITH TIME ZONE` with no `USING` clause, which makes PostgreSQL
 * reinterpret every stored naive timestamp in the session's `TimeZone` - silently shifting
 * it on any server not running UTC. Since `UTCDateTimeType` always writes UTC, the cast
 * must say so explicitly, which is why this never mutates `$schema` for these columns and
 * emits the `ALTER` as raw SQL instead.
 */
final class Version30100_22 extends AbstractMigration
{
    private const array TIMESTAMPABLE_COLUMNS = ['created', 'updated'];

    public function getDescription(): string
    {
        return 'Align created/updated (and payments.completed) to timestamptz with an explicit UTC cast on PostgreSQL';
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

        foreach ($this->driftingColumns($schema, Types::DATETIME_MUTABLE) as [$table, $column]) {
            $this->addSql(sprintf(
                "ALTER TABLE %s ALTER %s TYPE TIMESTAMP(0) WITH TIME ZONE USING %s AT TIME ZONE 'UTC'",
                $table,
                $column,
                $column,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        if (! $this->platform instanceof PostgreSQLPlatform) {
            return;
        }

        foreach ($this->driftingColumns($schema, Types::DATETIMETZ_MUTABLE) as [$table, $column]) {
            $this->addSql(sprintf(
                "ALTER TABLE %s ALTER %s TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING %s AT TIME ZONE 'UTC'",
                $table,
                $column,
                $column,
            ));
        }
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function driftingColumns(Schema $schema, string $currentType): array
    {
        $columns = [];

        foreach ($schema->getTables() as $table) {
            $candidates = self::TIMESTAMPABLE_COLUMNS;

            if ($table->getName() === 'payments') {
                $candidates[] = 'completed';
            }

            foreach ($candidates as $columnName) {
                if (! $table->hasColumn($columnName)) {
                    continue;
                }

                if ($table->getColumn($columnName)->getTypeName() !== $currentType) {
                    continue;
                }

                $columns[] = [$table->getName(), $columnName];
            }
        }

        return $columns;
    }
}
