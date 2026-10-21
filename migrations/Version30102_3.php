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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\Migrations\AbstractMigration;
use function strtoupper;

final class Version30102_3 extends AbstractMigration
{
    private const string OLD_TABLE = 'recurringinvoice_contact';

    private const string NEW_TABLE = 'recurringinvoice_contacts';

    /**
     * The index and foreign key names Doctrine's `_generateIdentifierName()` produces for each
     * table/column pair (`FK`/`IDX` + the hex CRC32 of the table name followed by the hex CRC32
     * of the column name), keyed by the upper-cased name on the old table.
     */
    private const array UP_NAMES = [
        'FK_1673913EE31CCDF' => 'FK_DA79A131E31CCDF',
        'FK_1673913EE7A1254A' => 'FK_DA79A131E7A1254A',
        'IDX_1673913EE31CCDF' => 'IDX_DA79A131E31CCDF',
        'IDX_1673913EE7A1254A' => 'IDX_DA79A131E7A1254A',
    ];

    private const array DOWN_NAMES = [
        'FK_DA79A131E31CCDF' => 'FK_1673913EE31CCDF',
        'FK_DA79A131E7A1254A' => 'FK_1673913EE7A1254A',
        'IDX_DA79A131E31CCDF' => 'IDX_1673913EE31CCDF',
        'IDX_DA79A131E7A1254A' => 'IDX_1673913EE7A1254A',
    ];

    public function getDescription(): string
    {
        return 'Rename recurringinvoice_contact to recurringinvoice_contacts to match the RecurringInvoice::$users join table mapping, keeping its rows, indexes and foreign keys';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * doctrine:schema:update's diff cannot see a rename: DBAL's comparator matches tables by name,
     * so renaming the table on $schema would plan `CREATE TABLE recurringinvoice_contacts` plus
     * `DROP TABLE recurringinvoice_contact`, deleting every recurring-invoice/contact association.
     * The table is instead renamed in place with the platform's own rename statement, and the
     * index and foreign key renames go through a hand-built TableDiff, because the comparator
     * also treats two foreign keys that differ only by name as equal and would leave them alone.
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::NEW_TABLE) || ! $schema->hasTable(self::OLD_TABLE)) {
            return;
        }

        $this->renameTable($schema, self::OLD_TABLE, self::NEW_TABLE, self::UP_NAMES);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::OLD_TABLE) || ! $schema->hasTable(self::NEW_TABLE)) {
            return;
        }

        $this->renameTable($schema, self::NEW_TABLE, self::OLD_TABLE, self::DOWN_NAMES);
    }

    /**
     * @param array<string, string> $names
     */
    private function renameTable(Schema $schema, string $from, string $to, array $names): void
    {
        $this->addSql($this->platform->getRenameTableSQL($from, $to));

        $renamed = $schema->getTable($from)->edit()->setUnquotedName($to)->create();

        $renamedIndexes = [];

        foreach ($renamed->getIndexes() as $index) {
            $newName = $names[strtoupper($index->getObjectName()->getIdentifier()->getValue())] ?? null;

            if (null !== $newName) {
                $renamedIndexes[$index->getObjectName()->getIdentifier()->getValue()] = $index->edit()->setUnquotedName($newName)->create();
            }
        }

        $droppedForeignKeys = [];
        $addedForeignKeys = [];

        foreach ($renamed->getForeignKeys() as $foreignKey) {
            $newName = $names[strtoupper((string) $foreignKey->getObjectName()?->getIdentifier()->getValue())] ?? null;

            if (null !== $newName) {
                $droppedForeignKeys[] = $foreignKey;
                $addedForeignKeys[] = $foreignKey->edit()->setUnquotedName($newName)->create();
            }
        }

        // Three diffs, because MariaDB below 10.5.2 renames an index by dropping and re-creating it:
        // in one diff it would also drop and restore every foreign key on that index under its old
        // name, and it adds foreign keys before dropping indexes, so the old index would then be
        // needed by the new foreign key and could not be dropped.
        $withoutForeignKeys = $renamed->edit()->setForeignKeyConstraints()->create();

        $this->alterTable(new TableDiff($renamed, droppedForeignKeys: $droppedForeignKeys));
        $this->alterTable(new TableDiff($withoutForeignKeys, renamedIndexes: $renamedIndexes));
        $this->alterTable(new TableDiff($withoutForeignKeys, addedForeignKeys: $addedForeignKeys));
    }

    private function alterTable(TableDiff $diff): void
    {
        foreach ($this->platform->getAlterTableSQL($diff) as $sql) {
            $this->addSql($sql);
        }
    }
}
