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
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Uid\Ulid;

final class Version20200 extends AbstractMigration
{
    private const ALL_TABLES = [
        'app_config',
        'clients',
        'addresses',
        'contact_types',
        'contacts',
        'client_credit',
        'contact_details',
        'invoices',
        'invoice_lines',
        'payment_methods',
        'payments',
        'quotes',
        'quote_lines',
        'recurring_invoices',
        'tax_rates',
        'api_tokens',
        'api_token_history',
    ];

    private LoggerInterface $logger;

    private Schema $toSchema;

    /**
     * @var array<string, array{0: string, 1: string[]}>
     */
    private array $tablesForForeignKeys = [];

    /**
     * @var list<string>
     */
    private array $tablesWithCompanyId = [];

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);

        $this->logger = $logger;
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySqlPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function preUp(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
            $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        }

        if ($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $this->connection->executeQuery('PRAGMA foreign_keys = OFF');
        }
    }

    public function up(Schema $schema): void
    {
        $originalSchema = clone $schema;
        $this->toSchema = clone $originalSchema;

        $schema
            ->getTable('payments')
            ->modifyColumn(
                'details',
                [
                    'type' => new JsonType(),
                    'notnull' => true,
                ]
            );

        $this->connection
            ->insert(
                'app_config',
                [
                    'setting_key' => 'invoice/watermark',
                    'setting_value' => true,
                    'description' => 'Display a watermark on the invoice with the status',
                    'field_type' => CheckboxType::class
                ],
            );

        $this->connection
            ->insert(
                'app_config',
                [
                    'setting_key' => 'quote/watermark',
                    'setting_value' => true,
                    'description' => 'Display a watermark on the quote with the status',
                    'field_type' => CheckboxType::class
                ],
            );

        $companiesTable = $schema->createTable('companies');
        $userCompaniesTable = $schema->createTable('user_company');

        $companiesTable->addColumn('id', UlidType::NAME);
        $companiesTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $companiesTable->setPrimaryKey(['id']);

        $userCompaniesTable->addColumn('user_id', 'integer', ['notnull' => true]);
        $userCompaniesTable->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $userCompaniesTable->setPrimaryKey(['user_id', 'company_id']);
        $userCompaniesTable->addIndex(['user_id']);
        $userCompaniesTable->addIndex(['company_id']);

        $appConfigTable = $this->addCompanyToTable($schema, 'app_config');
        $clientsTable = $this->addCompanyToTable($schema, 'clients');
        $clientCreditTable = $this->addCompanyToTable($schema, 'client_credit');

        foreach ($appConfigTable->getIndexes() as $index) {
            if ($index->isUnique() && $index->getColumns() === ['setting_key']) {
                $appConfigTable->dropIndex($index->getName());
            }
        }

        $appConfigTable->addUniqueIndex(['setting_key', 'company_id']);

        foreach ($clientsTable->getIndexes() as $index) {
            if ($index->isUnique() && $index->getColumns() === ['name']) {
                $clientsTable->dropIndex($index->getName());
            }
        }

        foreach ($clientCreditTable->getIndexes() as $index) {
            if ($index->isUnique() && $index->getColumns() === ['client_id']) {
                $clientCreditTable->dropIndex($index->getName());
            }
        }

        $clientCreditTable->addUniqueIndex(['client_id', 'company_id']);

        $clientsTable->addUniqueConstraint(['company_id', 'name']);

        $this->addCompanyToTable($schema, 'addresses');
        $this->addCompanyToTable($schema, 'contact_types');
        $this->addCompanyToTable($schema, 'contacts');
        $this->addCompanyToTable($schema, 'contact_details');
        $this->addCompanyToTable($schema, 'invoices');
        $this->addCompanyToTable($schema, 'invoice_lines');
        $this->addCompanyToTable($schema, 'payment_methods');
        $this->addCompanyToTable($schema, 'payments');
        $this->addCompanyToTable($schema, 'quotes');
        $this->addCompanyToTable($schema, 'quote_lines');
        $this->addCompanyToTable($schema, 'recurring_invoices');
        $this->addCompanyToTable($schema, 'tax_rates');
        $this->addCompanyToTable($schema, 'api_tokens');
        $this->addCompanyToTable($schema, 'api_token_history');

        foreach (
            $this->platform
                ->getAlterSchemaSQL(
                    $this
                        ->connection
                        ->createSchemaManager()
                        ->createComparator()
                        ->compareSchemas($originalSchema, $this->toSchema)
                ) as $sql
        ) {
            $this->addSql($sql);
        }

        $userInvitationsTable = $schema->createTable('user_invitations');
        $userInvitationsTable->addColumn('id', UlidType::NAME);
        $userInvitationsTable->addColumn('invited_by_id', 'integer', ['notnull' => true]);
        $userInvitationsTable->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $userInvitationsTable->addColumn('email', 'string', ['length' => 255, 'notnull' => true]);
        $userInvitationsTable->addColumn('status', 'string', ['length' => 255, 'notnull' => true]);
        $userInvitationsTable->addColumn('created', 'datetimetz_immutable', ['notnull' => true]);
        $userInvitationsTable->setPrimaryKey(['id', 'company_id']);
        $userInvitationsTable->addIndex(['company_id']);
        $userInvitationsTable->addForeignKeyConstraint(
            $companiesTable,
            ['company_id'],
            ['id'],
        );
        $userInvitationsTable->addForeignKeyConstraint(
            $schema->getTable('users'),
            ['invited_by_id'],
            ['id'],
        );
    }

    public function postUp(Schema $schema): void
    {
        $fromSchema = $this->connection->createSchemaManager()->introspectSchema();

        $users = $this->connection
            ->createQueryBuilder()
            ->select('u.id')
            ->from('users', 'u')
            ->fetchAllAssociative();

        $companyName = $this->connection
            ->createQueryBuilder()
            ->select('s.setting_value')
            ->from('app_config', 's')
            ->where('s.setting_key = :settingKey')
            ->setParameter('settingKey', 'system/company/company_name')
            ->fetchOne();

        $companyId = new Ulid();

        $this->connection
            ->insert('companies', ['name' => $companyName, 'id' => $companyId->toBinary()]);

        foreach (self::ALL_TABLES as $table) {
            // @phpstan-ignore-next-line
            $this->connection->update($table, ['company_id' => $companyId->toBinary()], ['1' => '1']);
        }

        foreach ($users as $user) {
            $this->connection
                ->insert('user_company', [
                    'user_id' => $user['id'],
                    'company_id' => $companyId->toBinary(),
                ]);
        }

        foreach ($this->tablesForForeignKeys as $tableB => [$foreignTableName, $foreignKeyName]) {
            $schema->getTable($tableB)->addForeignKeyConstraint(
                $foreignTableName,
                $foreignKeyName,
                ['id']
            );
        }

        foreach ($this->tablesWithCompanyId as $tableName) {
            $schema->getTable($tableName)->addForeignKeyConstraint($schema->getTable('companies'), ['company_id'], ['id']);
        }

        foreach (
            $this->platform
                ->getAlterSchemaSQL(
                    $this
                        ->connection
                        ->createSchemaManager()
                        ->createComparator()
                        ->compareSchemas($fromSchema, $schema)
                ) as $sql
        ) {
            $this->logger->log(LogLevel::DEBUG, '{query}', ['query' => $sql]);
            $this->connection->executeQuery($sql);
        }

        if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
            $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function preDown(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
            $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => 'invoice/watermark']);
        $this->connection->delete('app_config', ['setting_key' => 'quote/watermark']);

        foreach ($schema->getTables() as $table) {
            if ($table->hasColumn('company_id')) {
                foreach ($table->getIndexes() as $index) {
                    if ($index->getColumns() === ['company_id']) {
                        $table->dropIndex($index->getName());
                    }
                }

                $table->modifyColumn(
                    'id',
                    [
                        'type' => new IntegerType(),
                        'notnull' => true,
                        'autoincrement' => true,
                    ]
                );

                $table->dropPrimaryKey();
                $table->setPrimaryKey(['id']);
            }
        }

        $schema->dropTable('companies');
        $schema->dropTable('user_company');
        $schema->dropTable('user_invitations');
    }

    public function postDown(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
            $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @throws SchemaException|Exception
     */
    private function addCompanyToTable(Schema $schema, string $tableName): Table
    {
        $this->tablesWithCompanyId[] = $tableName;

        $table = $schema->getTable($tableName);

        $table->addColumn('company_id', UlidType::NAME, ['notnull' => false]);
        $table->addIndex(['company_id']);

        $table->modifyColumn(
            'id',
            [
                'type' => new IntegerType(),
                'notnull' => true,
                'autoincrement' => false,
            ]
        );

        // remove all foreign keys on all tables that are part of this tables primary key
        foreach ($this->toSchema->getTables() as $tableA) {
            foreach ($tableA->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getForeignTableName() === $tableName && $foreignKey->getForeignColumns() === ['id']) {
                    $this->tablesForForeignKeys[$tableA->getName()] = [$foreignKey->getForeignTableName(), $foreignKey->getLocalColumns()];
                    $tableA->removeForeignKey($foreignKey->getName());
                }
            }
        }

        $table->dropPrimaryKey();
        $table->setPrimaryKey(['id', 'company_id']);

        if ($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $table->getColumn('company_id')->setNotnull(false);
        }

        return $table;
    }
}
