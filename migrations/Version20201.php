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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function array_flip;
use function count;
use function in_array;

final class Version20201 extends AbstractMigration
{
    private Schema $schema;

    private Schema $fromSchema;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);

        $this->logger = $logger;
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function preUp(Schema $schema): void
    {
        // Trigger DB introspection to get the schema at the current state
        // otherwise this only happens after the migration is applied which
        // means we can't compare the schema before and after the migration
        $schema->getTables();
        $this->fromSchema = clone $schema;
    }

    public function up(Schema $schema): void
    {
        $this->schema = clone $schema;

        $this->schema->getTable('quote_contact')
            ->dropPrimaryKey();

        $this->schema->getTable('invoice_contact')
            ->dropPrimaryKey();

        $this->schema->getTable('recurringinvoice_contact')
            ->dropPrimaryKey();

        $invoiceContact = $this->schema->getTable('invoice_contact');
        $invoiceContact->addColumn('company_id', UlidType::NAME, ['notnull' => false]);
        $invoiceContact->addIndex(['invoice_id', 'company_id']);
        $invoiceContact->setPrimaryKey(['invoice_id', 'contact_id']);

        $recurringInvoiceContact = $this->schema->getTable('recurringinvoice_contact');
        $recurringInvoiceContact->addColumn('company_id', UlidType::NAME, ['notnull' => false]);
        $recurringInvoiceContact->addIndex(['recurringinvoice_id', 'company_id']);
        $recurringInvoiceContact->setPrimaryKey(['recurringinvoice_id', 'contact_id']);

        $quoteContact = $this->schema->getTable('quote_contact');
        $quoteContact->addColumn('company_id', UlidType::NAME, ['notnull' => false]);
        $quoteContact->addIndex(['quote_id', 'company_id']);
        $quoteContact->setPrimaryKey(['quote_id', 'contact_id']);

        // Apply these through $this->schema like everything else. $schema is only diffed by
        // Doctrine once up() returns, against the state introspected before any of the work below
        // ran — so a change staged on it is emitted from a stale snapshot. For payment_methods that
        // meant recreating the table with its original INTEGER id long after the rows had been
        // converted to ulids, which SQLite rejects outright as a datatype mismatch.
        $this->schema->dropTable('ext_log_entries');

        $paymentMethods = $this->schema->getTable('payment_methods');

        foreach ($paymentMethods->getIndexes() as $index) {
            if ($index->isUnique() && ! $index->isPrimary()) {
                $paymentMethods->dropIndex($index->getName());
            }
        }

        $this->schema
            ->getTable('invoices')
            ->addColumn('invoice_id', 'string', ['notnull' => false, 'length' => 255]);

        $this->schema
            ->getTable('quotes')
            ->addColumn('quote_id', 'string', ['notnull' => false, 'length' => 255]);

        $this->persistChanges();

        $this
            ->connection
            ->createQueryBuilder()
            ->update('invoices')
            ->set('invoice_id', 'id')
            ->executeQuery();

        $this
            ->connection
            ->createQueryBuilder()
            ->update('quotes')
            ->set('quote_id', 'id')
            ->executeQuery();

        $this->schema
            ->getTable('invoices')
            ->modifyColumn('invoice_id', ['notnull' => true, 'length' => 255]);

        $this->schema
            ->getTable('quotes')
            ->modifyColumn('quote_id', ['notnull' => true, 'length' => 255]);

        $clientCreditTable = $this->schema->getTable('client_credit');

        foreach ($clientCreditTable->getIndexes() as $index) {
            if ($index->isUnique() && ! $index->isPrimary()) {
                $clientCreditTable->dropIndex($index->getName());
            }
        }

        foreach ($this->connection->createSchemaManager()->listTables() as $table) {
            if (
                $table->hasColumn('company_id') &&
                $table->hasColumn('id') &&
                $table->getColumn('id')->getType() instanceof IntegerType
            ) {
                $this->migrate($table->getName());
            }
        }

        $this->schema->getTable('user_company')
            ->addForeignKeyConstraint('users', ['user_id'], ['id']);

        $this->schema
            ->getTable('user_company')
            ->dropPrimaryKey();

        $this->persistChanges();

        $this->migrate('users', false);

        // user_company holds a foreign key to users, so the migrate() call above has just restored
        // its original primary key. Drop it again before defining the intended one — setting a
        // second primary key on the same table is an error.
        $userCompany = $this->schema->getTable('user_company');

        if ($userCompany->getPrimaryKey() instanceof Index) {
            $userCompany->dropPrimaryKey();
        }

        $userCompany->setPrimaryKey(['company_id', 'user_id']);

        $this->persistChanges();
    }

    public function down(Schema $schema): void
    {
        $quoteContact = $schema->getTable('quote_contact');
        $quoteContact->dropPrimaryKey();
        $quoteContact->setPrimaryKey(['quote_id', 'contact_id']);
        $quoteContact->dropColumn('company_id');

        $invoiceContact = $schema->getTable('invoice_contact');
        $invoiceContact->dropPrimaryKey();
        $invoiceContact->setPrimaryKey(['invoice_id', 'contact_id']);
        $invoiceContact->dropColumn('company_id');

        $recurringInvoiceContact = $schema->getTable('recurringinvoice_contact');
        $recurringInvoiceContact->dropPrimaryKey();
        $recurringInvoiceContact->setPrimaryKey(['recurringinvoice_id', 'contact_id']);
        $recurringInvoiceContact->dropColumn('company_id');

        $extLogEntries = $schema->createTable('ext_log_entries');
        $extLogEntries->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $extLogEntries->addColumn('action', 'string', ['length' => 8, 'notnull' => true]);
        $extLogEntries->addColumn('logged_at', 'datetime', ['notnull' => true]);
        $extLogEntries->addColumn('object_id', 'string', ['length' => 64, 'notnull' => false]);
        $extLogEntries->addColumn('object_class', 'string', ['length' => 255, 'notnull' => true]);
        $extLogEntries->addColumn('version', 'integer', ['notnull' => true]);
        $extLogEntries->addColumn('data', 'array', ['notnull' => false]);
        $extLogEntries->addColumn('username', 'string', ['length' => 255, 'notnull' => false]);
        $extLogEntries->addIndex(['object_class'], 'log_class_lookup_idx');
        $extLogEntries->addIndex(['logged_at'], 'log_date_lookup_idx');
        $extLogEntries->addIndex(['username'], 'log_user_lookup_idx');
        $extLogEntries->addIndex(['object_id', 'object_class', 'version'], 'log_version_lookup_idx');
        $extLogEntries->addOption('row_format', 'DYNAMIC');
    }

    /**
     * @throws Exception|RuntimeException|\Exception
     */
    public function migrate(string $tableName, bool $linkCompany = true): void
    {
        $uuidColumnName = '__uuid__';

        $this->write('Migrating ' . $tableName . '.id to UUIDs...');
        $foreignKeys = $this->getTableForeignKeys($tableName);
        $this->addUuidFields($tableName, $uuidColumnName, $foreignKeys);

        $this->persistChanges();

        $uuids = $this->generateUuidsToReplaceIds($tableName, $uuidColumnName, $linkCompany);

        $this->addUuidsToTablesWithFK($foreignKeys, $uuids, $linkCompany);
        $this->deletePreviousFKs($foreignKeys);

        $this->persistChanges();

        $this->renameNewFKsToPreviousNames($foreignKeys);

        $this->persistChanges();

        $this->dropIdPrimaryKeyAndSetUuidToPrimaryKey($tableName, $uuidColumnName);

        $this->persistChanges();

        $this->restoreConstraintsAndIndexes($tableName, $foreignKeys);

        $this->persistChanges();

        $this->write('Successfully migrated ' . $tableName . '.id to UUIDs!');
    }

    private function isForeignKeyNullable(Table $table, string $key): bool
    {
        foreach ($table->getColumns() as $column) {
            if ($column->getName() === $key) {
                return ! $column->getNotnull();
            }
        }

        throw new RuntimeException('Unable to find ' . $key . 'in ' . $table->getName());
    }

    /**
     * @return array<array<string|array<string>>>
     * @throws Exception|RuntimeException
     */
    private function getTableForeignKeys(string $tableName): array
    {
        $schemaManager = $this->connection->createSchemaManager();

        $allForeignKeys = [];

        foreach ($schemaManager->listTables() as $table) {
            $foreignKeys = $schemaManager->listTableForeignKeys($table->getName());
            foreach ($foreignKeys as $foreignKey) {
                $key = $foreignKey->getLocalColumns()[0];
                if ($foreignKey->getForeignTableName() === $tableName) {
                    $fk = [
                        'table' => $table->getName(),
                        'key' => $key,
                        'tmpKey' => $key . '_to_uuid',
                        'nullable' => $this->isForeignKeyNullable($table, $key),
                        'name' => $foreignKey->getName(),
                        'primaryKey' => $table->getPrimaryKey() ? $table->getPrimaryKey()->getColumns() : [],
                    ];

                    if ($foreignKey->onDelete()) {
                        $fk['onDelete'] = $foreignKey->onDelete();
                    }
                    $allForeignKeys[] = $fk;
                }
            }
        }

        if (count($allForeignKeys) > 0) {
            $this->write('-> Detected the following foreign keys :');
            foreach ($allForeignKeys as $fk) {
                $this->write('  * ' . $fk['table'] . '.' . $fk['key']);
            }
        } else {
            $this->write('-> 0 foreign key detected.');
        }

        return $allForeignKeys;
    }

    /**
     * @param array<array<string|array<string>>> $foreignKeys
     * @throws SchemaException
     */
    private function addUuidFields(string $tableName, string $uuidColumnName, array $foreignKeys = []): void
    {
        $table = $this->schema->getTable($tableName);

        // Nullable on purpose: the column is populated straight after this, and only becomes the
        // NOT NULL primary key in dropIdPrimaryKeyAndSetUuidToPrimaryKey(). Adding it NOT NULL up
        // front is rejected outright by SQLite ("Cannot add a NOT NULL column with default value
        // NULL") and fails on PostgreSQL as soon as the table has rows.
        $table->addColumn($uuidColumnName, UlidType::NAME, ['notnull' => false]);

        foreach ($foreignKeys as $fk) {
            $fkTable = $this->schema->getTable($fk['table']);

            $fkTable->addColumn($fk['tmpKey'], UlidType::NAME, ['notnull' => ! $this->foreignColumnShouldBeNullable($fk)]);
        }
    }

    /**
     * @return array<string, array<Ulid>>
     * @throws \Exception
     */
    private function generateUuidsToReplaceIds(string $tableName, string $uuidColumnName, bool $linkCompany = true): array
    {
        $fields = ['id'];

        if ($linkCompany) {
            $fields[] = 'company_id';
        }

        $records = $this->connection->createQueryBuilder()
            ->select(...$fields)
            ->from($tableName)
            ->fetchAllAssociative();

        $this->write('-> Generating ' . count($records) . ' UUID(s)...');

        $idToUuidMap = [];

        foreach ($records as $record) {
            $id = $record['id'];
            $uuid = new Ulid();

            if ($linkCompany) {
                $idToUuidMap[$record['company_id']][$id] = $uuid;
            } else {
                $idToUuidMap[$id] = $uuid;
            }

            // Match on the primary key alone. Adding company_id would be redundant — id already
            // identifies the row — and actively wrong when it is NULL, because Connection::update()
            // renders that as `company_id = NULL`, which matches nothing. Rows seeded before
            // companies existed (all 23 app_config settings) would keep a NULL uuid and then break
            // when it is promoted to the NOT NULL primary key.
            $this->connection->update(
                $tableName,
                [$uuidColumnName => $uuid],
                ['id' => $id],
                [$uuidColumnName => UlidType::NAME]
            );
        }

        return $idToUuidMap;
    }

    /**
     * @param array<array<string|array<string>>> $foreignKeys
     * @param array<string, array<Ulid>> $idToUuidMap
     * @throws Exception
     */
    private function addUuidsToTablesWithFK(array $foreignKeys, array $idToUuidMap, bool $linkCompany = true): void
    {
        $this->write('-> Adding UUIDs to tables with foreign keys...');
        foreach ($foreignKeys as $fk) {
            $selectPk = implode(',', $fk['primaryKey']);

            try {
                $fieldsSelect = [$selectPk . ', ' . $fk['key'], $fk['key']];

                if ($linkCompany) {
                    $fieldsSelect[] = 'company_id';
                }

                $records = $this->connection->createQueryBuilder()
                    ->select(...$fieldsSelect)
                    ->from($fk['table'])
                    ->fetchAllAssociative();
            } catch (\Exception $e) {
                // TODO: Table doesn't have company id yet (E.G invoice_contact), so we need a different way of updating the data
                $this->write('  * Unable to fetch records from "' . $fk['table'] . '"');
                continue;
            }

            $this->write('  * Adding ' . count($records) . ' UUIDs to "' . $fk['table'] . '.' . $fk['key'] . '"');

            foreach ($records as $record) {
                if (! $record[$fk['key']]) {
                    continue;
                }

                if ($linkCompany && Ulid::fromString($record['company_id'])->toString() === '00000000-0000-0000-0000-000000000000') {
                    continue;
                }

                $queryPk = array_flip($fk['primaryKey']);
                foreach ($queryPk as $key => $value) {
                    $queryPk[$key] = $record[$key];
                }

                if ($linkCompany) {
                    $uuid = $idToUuidMap[$record['company_id']][$record[$fk['key']]];
                    $queryPk['company_id'] = $record['company_id'];
                } else {
                    $uuid = $idToUuidMap[$record[$fk['key']]];
                }

                /** @var Ulid $uuid */
                $this->connection->update(
                    $fk['table'],
                    [
                        $fk['tmpKey'] => $uuid->toString() !== '00000000-0000-0000-0000-000000000000' ? $uuid : null,
                    ],
                    $queryPk,
                    [
                        $fk['tmpKey'] => UlidType::NAME,
                    ]
                );
            }
        }
    }

    /**
     * @param array<array<string|array<string>>> $foreignKeys
     * @throws Exception
     */
    private function deletePreviousFKs(array $foreignKeys): void
    {
        $this->write('-> Deleting previous id foreign keys...');
        foreach ($foreignKeys as $fk) {
            $table = $this->schema->getTable($fk['table']);

            $table->removeForeignKey($fk['name']);

            // DBAL 4 no longer drops the primary key automatically when one of its
            // columns is removed, so a column that is part of a composite primary key
            // can't be dropped while the key still references it. Drop the primary key
            // here; restoreConstraintsAndIndexes() re-adds it from $fk['primaryKey'].
            $primaryKey = $table->getPrimaryKey();
            if ($primaryKey instanceof Index && in_array($fk['key'], $primaryKey->getColumns(), true)) {
                $table->dropPrimaryKey();
            }

            $table->dropColumn($fk['key']);

            foreach ($table->getIndexes() as $index) {
                if ($index->getColumns() === [$fk['key']]) {
                    $table->dropIndex($index->getName());
                }
            }
        }
    }

    /**
     * @param array<array<string|array<string>>> $foreignKeys
     * @throws Exception
     */
    private function renameNewFKsToPreviousNames(array $foreignKeys): void
    {
        $this->write('-> Renaming temporary uuid foreign keys to previous foreign keys names...');
        foreach ($foreignKeys as $fk) {
            $table = $this->schema->getTable($fk['table']);
            $table->dropColumn($fk['tmpKey']);

            $table->addColumn($fk['key'], UlidType::NAME, ['notnull' => ! $this->foreignColumnShouldBeNullable($fk)]);
        }
    }

    /**
     * @throws SchemaException|Exception
     */
    private function dropIdPrimaryKeyAndSetUuidToPrimaryKey(string $tableName, string $uuidColumnName): void
    {
        $this->write('-> Creating the uuid primary key...');

        $table = $this->schema->getTable($tableName);
        $table->dropPrimaryKey();
        $table->dropColumn('id');

        $this->persistChanges();

        // Add the new id on its own, copy the generated ulids across explicitly, and only then
        // tighten it into the primary key. Dropping __uuid__ in the same diff would leave Doctrine
        // to infer a column rename to carry the data over — a heuristic that only fires when both
        // definitions match exactly, which this migration depended on without saying so.
        $table->addColumn('id', UlidType::NAME, ['notnull' => false]);

        $this->persistChanges();

        $this->connection->executeStatement(sprintf('UPDATE %s SET id = %s', $tableName, $uuidColumnName));

        $table->dropColumn($uuidColumnName);
        $table->modifyColumn('id', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param array<array<string|array<string>>> $foreignKeys
     * @throws Exception
     */
    private function restoreConstraintsAndIndexes(string $tableName, array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreignKey) {
            $table = $this->schema->getTable($foreignKey['table']);

            if (isset($foreignKey['primaryKey']) && [] !== $foreignKey['primaryKey']) {
                try {
                    $table->setPrimaryKey($foreignKey['primaryKey']);
                } catch (\Exception $e) {
                }
            }

            $table->addForeignKeyConstraint(
                $tableName,
                [$foreignKey['key']],
                ['id'],
            );
        }
    }

    /**
     * @throws Exception
     */
    private function persistChanges(): void
    {
        foreach (
            $this->platform
                ->getAlterSchemaSQL(
                    $this
                        ->connection
                        ->createSchemaManager()
                        ->createComparator()
                        ->compareSchemas($this->fromSchema, $this->schema)
                ) as $sql
        ) {
            $this->logger->log(LogLevel::DEBUG, '{query}', ['query' => $sql]);
            $this->connection->executeQuery($sql);
        }

        $this->fromSchema = clone $this->schema;
    }

    /**
     * @param array<string, mixed> $foreignKey
     */
    private function foreignColumnShouldBeNullable(array $foreignKey): bool
    {
        if ($foreignKey['table'] === 'invoice_lines') {
            return $foreignKey['key'] === 'invoice_id' ||
                $foreignKey['key'] === 'recurringInvoice_id' ||
                $foreignKey['key'] === 'tax_id';
        }

        if ($foreignKey['table'] === 'invoices') {
            return $foreignKey['key'] === 'quote_id';
        }

        if ($foreignKey['table'] === 'quote_lines') {
            return $foreignKey['key'] === 'quote_id' || $foreignKey['key'] === 'tax_id';
        }

        if ($foreignKey['table'] === 'invoice_contact') {
            return $foreignKey['key'] === 'contact_id' || $foreignKey['key'] === 'invoice_id';
        }

        if ($foreignKey['table'] === 'quote_contact') {
            return $foreignKey['key'] === 'contact_id' || $foreignKey['key'] === 'quote_id';
        }

        if ($foreignKey['table'] === 'recurringinvoice_contact') {
            return $foreignKey['key'] === 'contact_id' || $foreignKey['key'] === 'recurringinvoice_id';
        }

        return $foreignKey['nullable'];
    }
}
