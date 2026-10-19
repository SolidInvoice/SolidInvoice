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
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LogicException;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as MessengerConnection;

final class Version30102_1 extends AbstractMigration
{
    private const string TABLE_NAME = 'messenger_messages';

    public function getDescription(): string
    {
        return 'Add the messenger_messages table, so the failed transport does not issue DDL at runtime on first send';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE_NAME)) {
            return;
        }

        // vendor/symfony/doctrine-messenger/Transport/Connection.php is the authority on the
        // column set for this table, and it has changed across Symfony minors. Building it
        // this way, instead of hand-rolling the DDL, keeps the migration correct when Symfony
        // changes it.
        //
        // configureSchema() can no longer mutate $schema in place: on this DBAL, Schema::edit()
        // exists, so Connection::addTableToSchema() takes the editor path and returns a new
        // Schema instance instead. doctrine/migrations diffs the exact Schema object up() was
        // handed, so that return value cannot simply replace $schema. Build on a throwaway
        // Schema instead, and copy the table it produced onto $schema.
        $built = new MessengerConnection(['table_name' => self::TABLE_NAME], $this->connection)
            ->configureSchema(new Schema(), $this->connection, static fn (): bool => true);

        if (! $built->hasTable(self::TABLE_NAME)) {
            throw new LogicException(sprintf('%s::configureSchema() did not build the "%s" table.', MessengerConnection::class, self::TABLE_NAME));
        }

        if ($built->getSequences() !== []) {
            // Oracle >= 12.1 is the only platform for which configureSchema() also emits a sequence,
            // and src/InstallBundle/Doctrine/Drivers.php does not offer Oracle. Refuse rather than
            // create a table whose id default points at a sequence this migration never copies.
            throw new LogicException(sprintf('%s::configureSchema() emitted sequences, which this migration does not copy.', MessengerConnection::class));
        }

        $source = $built->getTable(self::TABLE_NAME);
        $table = $schema->createTable(self::TABLE_NAME);

        foreach ($source->getOptions() as $name => $value) {
            $table->addOption($name, $value);
        }

        foreach ($source->getColumns() as $column) {
            $table->addColumn($column->getName(), $column->getTypeName(), $this->columnOptions($column));
        }

        $primaryKey = $source->getPrimaryKeyConstraint();

        if ($primaryKey instanceof PrimaryKeyConstraint) {
            $table->addPrimaryKeyConstraint($primaryKey);
        }

        foreach ($source->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $table->addIndex($index->getUnquotedColumns(), $index->getName(), $index->getFlags(), $index->getOptions());
        }

        if ($schema->getTable(self::TABLE_NAME)->getColumns() === []) {
            throw new LogicException(sprintf('Copying the "%s" table definition onto the migration schema had no effect.', self::TABLE_NAME));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function columnOptions(Column $column): array
    {
        return [
            'notnull' => $column->getNotnull(),
            'length' => $column->getLength(),
            'precision' => $column->getPrecision(),
            'scale' => $column->getScale(),
            'unsigned' => $column->getUnsigned(),
            'fixed' => $column->getFixed(),
            'autoincrement' => $column->getAutoincrement(),
            'default' => $column->getDefault(),
            'comment' => $column->getComment(),
            'columnDefinition' => $column->getColumnDefinition(),
            'values' => $column->getValues(),
            'platformOptions' => $column->getPlatformOptions(),
        ];
    }

    public function down(Schema $schema): void
    {
        if (! $schema->hasTable(self::TABLE_NAME)) {
            return;
        }

        $schema->dropTable(self::TABLE_NAME);
    }
}
