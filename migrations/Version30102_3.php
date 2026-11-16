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
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\Migrations\AbstractMigration;
use function array_flip;
use function in_array;
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

    /**
     * Version20201 indexed (recurringinvoice_id, company_id), and Version20305 then dropped
     * company_id. PostgreSQL drops the index with the column, but MySQL and MariaDB keep it on
     * recurringinvoice_id alone, where it duplicates IDX_DA79A131E31CCDF and is not in the
     * mapping. It is not restored by down(), being redundant.
     */
    private const array REDUNDANT_INDEXES = ['IDX_1673913EE31CCDF979B1AD6'];

    private const array DOWN_NAMES = [
        'FK_DA79A131E31CCDF' => 'FK_1673913EE31CCDF',
        'FK_DA79A131E7A1254A' => 'FK_1673913EE7A1254A',
        'IDX_DA79A131E31CCDF' => 'IDX_1673913EE31CCDF',
        'IDX_DA79A131E7A1254A' => 'IDX_1673913EE7A1254A',
    ];

    /**
     * Columns the ORM mapping renamed to camelCase without a migration ever renaming them in the
     * database, keyed by table, then old name => new name.
     */
    private const array COLUMNS = [
        'reset_password_request' => [
            'hashed_token' => 'hashedToken',
            'requested_at' => 'requestedAt',
            'expires_at' => 'expiresAt',
        ],
        'user_invitations' => [
            'expires_at' => 'expiresAt',
            'reminder_sent_at' => 'reminderSentAt',
        ],
    ];

    public function getDescription(): string
    {
        return 'Rename the tables and columns whose ORM mapping was renamed without a migration (recurringinvoice_contact, reset_password_request and user_invitations), keeping their rows, indexes and foreign keys';
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
     * `DROP TABLE recurringinvoice_contact`, deleting every recurring-invoice/contact association,
     * and a renamed column reads as one dropped column plus one added, empty column. Every rename
     * here is instead planned directly with the platform: the table with its own rename
     * statement, and its indexes, foreign keys and the columns through hand-built TableDiffs,
     * because the comparator also treats two foreign keys that differ only by name as equal.
     *
     * Each rename is guarded on the old name still being there, so a fresh install, whose schema
     * is built straight from the ORM mapping, is a no-op.
     */
    public function up(Schema $schema): void
    {
        if (! $schema->hasTable(self::NEW_TABLE) && $schema->hasTable(self::OLD_TABLE)) {
            $this->renameTable($schema, self::OLD_TABLE, self::NEW_TABLE, self::UP_NAMES, self::REDUNDANT_INDEXES);
        }

        foreach (self::COLUMNS as $table => $columns) {
            $this->renameColumns($schema, $table, $columns);
        }
    }

    public function down(Schema $schema): void
    {
        if (! $schema->hasTable(self::OLD_TABLE) && $schema->hasTable(self::NEW_TABLE)) {
            $this->renameTable($schema, self::NEW_TABLE, self::OLD_TABLE, self::DOWN_NAMES);
        }

        foreach (self::COLUMNS as $table => $columns) {
            $this->renameColumns($schema, $table, array_flip($columns));
        }
    }

    /**
     * @param array<string, string> $columns old name => new name
     */
    private function renameColumns(Schema $schema, string $tableName, array $columns): void
    {
        if (! $schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        $changedColumns = [];

        foreach ($columns as $from => $to) {
            if ($table->hasColumn($from) && ! $table->hasColumn($to)) {
                $column = $table->getColumn($from);
                $changedColumns[$from] = new ColumnDiff($column, $column->edit()->setUnquotedName($to)->create());
            }
        }

        if ([] !== $changedColumns) {
            $this->alterTable(new TableDiff($table, changedColumns: $changedColumns));
        }
    }

    /**
     * @param array<string, string> $names
     * @param list<string>          $redundantIndexes
     */
    private function renameTable(Schema $schema, string $from, string $to, array $names, array $redundantIndexes = []): void
    {
        $this->addSql($this->platform->getRenameTableSQL($from, $to));

        $renamed = $schema->getTable($from)->edit()->setUnquotedName($to)->create();
        $withoutForeignKeys = $renamed->edit()->setForeignKeyConstraints()->create();
        $withRenamedIndexes = $withoutForeignKeys->edit();

        $renamedIndexes = [];
        $droppedIndexes = [];

        foreach ($renamed->getIndexes() as $index) {
            $oldName = $index->getObjectName()->getIdentifier()->getValue();
            $newName = $names[strtoupper($oldName)] ?? null;

            if (in_array(strtoupper($oldName), $redundantIndexes, true)) {
                $droppedIndexes[] = $index;
                $withRenamedIndexes->dropIndexByUnquotedName($oldName);
            } elseif (null !== $newName) {
                $renamedIndexes[$oldName] = $index->edit()->setUnquotedName($newName)->create();
                $withRenamedIndexes->renameIndexByUnquotedName($oldName, $newName);
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
        // needed by the new foreign key and could not be dropped. Each diff starts from the table
        // as the previous one left it, because SQLite rebuilds the whole table from that state.
        $this->alterTable(new TableDiff($renamed, droppedForeignKeys: $droppedForeignKeys));
        $this->alterTable(new TableDiff($withoutForeignKeys, droppedIndexes: $droppedIndexes, renamedIndexes: $renamedIndexes));
        $this->alterTable(new TableDiff($withRenamedIndexes->create(), addedForeignKeys: $addedForeignKeys));
    }

    private function alterTable(TableDiff $diff): void
    {
        foreach ($this->platform->getAlterTableSQL($diff) as $sql) {
            $this->addSql($sql);
        }
    }
}
