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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use function assert;
use function str_repeat;

final class Version20201 extends AbstractMigration
{
    private LoggerInterface $logger;
    private Schema $fromSchema;
    private Schema $schema;

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

        $schema->getTable('quote_contact')
            ->dropPrimaryKey();

        $schema->getTable('invoice_contact')
            ->dropPrimaryKey();

        $schema->getTable('recurringinvoice_contact')
            ->dropPrimaryKey();

        $invoiceContact = $schema->getTable('invoice_contact');
        $invoiceContact->addColumn('company_id', 'uuid_binary_ordered_time', ['notnull' => false]);
        $invoiceContact->addIndex(['invoice_id', 'company_id']);
        $invoiceContact->setPrimaryKey(['invoice_id', 'contact_id', 'company_id']);

        $recurringInvoiceContact = $schema->getTable('recurringinvoice_contact');
        $recurringInvoiceContact->addColumn('company_id', 'uuid_binary_ordered_time', ['notnull' => false]);
        $recurringInvoiceContact->addIndex(['recurringinvoice_id', 'company_id']);
        $recurringInvoiceContact->setPrimaryKey(['recurringinvoice_id', 'contact_id', 'company_id']);

        $quoteContact = $schema->getTable('quote_contact');
        $quoteContact->addColumn('company_id', 'uuid_binary_ordered_time', ['notnull' => false]);
        $quoteContact->addIndex(['quote_id', 'company_id']);
        $quoteContact->setPrimaryKey(['quote_id', 'contact_id', 'company_id']);

        $userTable = $this->schema->getTable('users');
        $userInvitationTable = $this->schema->getTable('user_invitations');
        $userCompanyTable = $this->schema->getTable('user_company');
        $apiTokenTable = $this->schema->getTable('api_tokens');

        $userTable->addColumn('id__uuid__', 'uuid_binary_ordered_time', ['notnull' => false]);
        $userInvitationTable->addColumn('invited_by_id__uuid__', 'uuid_binary_ordered_time', ['notnull' => false]);
        $userCompanyTable->addColumn('user_id__uuid__', 'uuid_binary_ordered_time', ['notnull' => false]);
        $apiTokenTable->addColumn('user_id__uuid__', 'uuid_binary_ordered_time', ['notnull' => false]);

        $this->persistChanges();

        $factory = clone Uuid::getFactory();
        assert($factory instanceof UuidFactory);

        $factory->setCodec(new OrderedTimeCodec(
            $factory->getUuidBuilder(),
        ));

        $users = $this->connection
            ->createQueryBuilder()
            ->select('u.id')
            ->from('users', 'u')
            ->fetchAllAssociative();

        foreach ($users as $user) {
            $userId = $factory->uuid1()->getBytes();

            $this->connection->update('users', ['id__uuid__' => $userId], ['id' => $user['id']]);
            $this->connection->update('user_invitations', ['invited_by_id__uuid__' => $userId], ['invited_by_id' => $user['id']]);
            $this->connection->update('user_company', ['user_id__uuid__' => $userId], ['user_id' => $user['id']]);
            $this->connection->update('api_tokens', ['user_id__uuid__' => $userId], ['user_id' => $user['id']]);
        }

        $this->setUuidOnTable($userInvitationTable->getName(), 'invited_by_id__uuid__');
        $this->setUuidOnTable($userCompanyTable->getName(), 'user_id__uuid__');
        $this->setUuidOnTable($apiTokenTable->getName(), 'user_id__uuid__');
        $this->schema->getTable('users')->dropPrimaryKey();
        $this->schema->getTable('user_company')->dropPrimaryKey();
        $this->persistChanges();
        $this->setUuidOnTable($userTable->getName(), 'id__uuid__');

        $this->schema->getTable('users')->setPrimaryKey(['id']);
        $this->schema->getTable('user_company')->setPrimaryKey(['user_id', 'company_id']);
        $this->persistChanges();
        $this->schema->getTable('user_invitations')->addForeignKeyConstraint('users', ['invited_by_id'], ['id']);
        $this->schema->getTable('user_company')->addForeignKeyConstraint('users', ['user_id'], ['id']);
        $this->schema->getTable('api_tokens')->addForeignKeyConstraint('users', ['user_id'], ['id']);

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
    }

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

    private function setUuidOnTable(string $tableName, string $columnName): void
    {
        $table = $this->schema->getTable($tableName);

        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getForeignTableName() === 'users' && $foreignKey->getForeignColumns() === ['id']) {
                $table->removeForeignKey($foreignKey->getName());
            }
        }

        $this->persistChanges();

        $table = $this->schema->getTable($tableName);

        $originalColumnName = str_replace('__uuid__', '', $columnName);
        $table->dropColumn($originalColumnName);
        $this->persistChanges();
        $table = $this->schema->getTable($tableName);
        $table->addColumn($originalColumnName, 'uuid_binary_ordered_time', ['notnull' => true]);

        $this->persistChanges();

        $records = $this->connection
            ->createQueryBuilder()
            ->select('t.' . $columnName)
            ->from($tableName, 't')
            ->fetchAllAssociative();

        foreach ($records as $record) {
            $this->connection->update($tableName, [$originalColumnName => $record[$columnName]], [$columnName => $record[$columnName]]);
        }

        $table = $this->schema->getTable($tableName);

        $table->dropColumn($columnName);

        $this->persistChanges();
    }
}
