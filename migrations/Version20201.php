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
        //$invoiceContact->setPrimaryKey(['invoice_id', 'contact_id', 'company_id']);

        $recurringInvoiceContact = $this->schema->getTable('recurringinvoice_contact');
        $recurringInvoiceContact->addColumn('company_id', UlidType::NAME, ['notnull' => false]);
        $recurringInvoiceContact->addIndex(['recurringinvoice_id', 'company_id']);
        //$recurringInvoiceContact->setPrimaryKey(['recurringinvoice_id', 'contact_id', 'company_id']);

        $quoteContact = $this->schema->getTable('quote_contact');
        $quoteContact->addColumn('company_id', UlidType::NAME, ['notnull' => false]);
        $quoteContact->addIndex(['quote_id', 'company_id']);
        //$quoteContact->setPrimaryKey(['quote_id', 'contact_id', 'company_id']);

        $schema->dropTable('ext_log_entries');

        foreach ($schema->getTable('payment_methods')->getIndexes() as $index) {
            if ($index->isUnique() && ! $index->isPrimary()) {
                $schema->getTable('payment_methods')->dropIndex($index->getName());
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
            ->update('invoices', 'i')
            ->set('i.invoice_id', 'i.id')
            ->executeQuery();

        $this
            ->connection
            ->createQueryBuilder()
            ->update('quotes', 'q')
            ->set('q.quote_id', 'q.id')
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

        $this->schema->getTable('user_company')
            ->setPrimaryKey(['company_id', 'user_id']);

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

        $table->addColumn($uuidColumnName, UlidType::NAME, ['notnull' => true]);

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
                $companyId = $record['company_id'];
                $idToUuidMap[$companyId][$id] = $uuid;
                $updateCriteria = ['id' => $id, 'company_id' => $companyId];
            } else {
                $idToUuidMap[$id] = $uuid;
                $updateCriteria = ['id' => $id];
            }

            $this->connection->update(
                $tableName,
                [$uuidColumnName => $uuid],
                $updateCriteria,
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
            $table->dropColumn($fk['key']);
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

        $table->dropColumn($uuidColumnName);
        $table->addColumn('id', UlidType::NAME, ['notnull' => true]);
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
                [],
                $foreignKey['name']
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
