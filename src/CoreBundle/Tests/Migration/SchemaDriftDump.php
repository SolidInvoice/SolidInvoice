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

use function array_filter;
use function array_map;
use function implode;
use function in_array;
use function preg_match;
use function preg_split;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

/**
 * {@see MigrationIsIdempotentAcrossLineagesTestCase} replays the migration chain only up to
 * the migration under test, so `doctrine:schema:update --dump-sql` always still reports every
 * table a *later* migration has not created yet. A plain substring match over the whole dump
 * then false-positives the moment a later table happens to share a column name with the one
 * the migration under test resolved - this strips that pending drift out before a subclass's
 * {@see MigrationIsIdempotentAcrossLineagesTestCase::resolvedDriftFragments()} is checked
 * against it, scoping the dump to tables that already exist in the lineage being tested.
 */
final class SchemaDriftDump
{
    private function __construct()
    {
    }

    /**
     * @param list<string> $existingTableNames
     */
    public static function scopedToExistingTables(string $dumpSql, array $existingTableNames): string
    {
        $existing = array_map(self::normalizeTableName(...), $existingTableNames);

        $statements = array_filter(preg_split('/;\s*\n/', $dumpSql) ?: [], static fn (string $statement): bool => '' !== trim($statement));

        $kept = array_filter(
            $statements,
            static function (string $statement) use ($existing): bool {
                $table = self::subjectTable(trim($statement));

                // An unrecognised statement shape is kept: silently going vacuous on a shape
                // this helper does not understand is worse than staying noisy.
                return null === $table || in_array(self::normalizeTableName($table), $existing, true);
            },
        );

        return implode(";\n", $kept);
    }

    private static function subjectTable(string $statement): ?string
    {
        if (1 === preg_match('/^(?:CREATE|ALTER)\s+TABLE\s+([^\s(]+)/i', $statement, $matches)) {
            return $matches[1];
        }

        if (1 === preg_match('/^CREATE\s+(?:UNIQUE\s+)?INDEX\s+\S+\s+ON\s+([^\s(]+)/i', $statement, $matches)) {
            return $matches[1];
        }

        if (1 === preg_match('/^DROP\s+INDEX\s+\S+\s+ON\s+([^\s(]+)/i', $statement, $matches)) {
            return $matches[1];
        }

        if (1 === preg_match('/^COMMENT\s+ON\s+COLUMN\s+(\S+)\.\S+/i', $statement, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function normalizeTableName(string $tableName): string
    {
        $lastDot = strrpos($tableName, '.');
        $unqualified = false !== $lastDot ? substr($tableName, $lastDot + 1) : $tableName;

        return strtolower(trim($unqualified, '"\'`'));
    }
}
