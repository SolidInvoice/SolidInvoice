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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Form\Type\BillingIdConfigurationType;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\UuidV1;

final class Version20300 extends AbstractMigration
{
    /**
     * @var array<string, list<string>>
     */
    private array $columnsToUpdate = [];

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySqlPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function preUp(Schema $schema): void
    {
        $this->connection
            ->update('client_credit', ['value_amount' => 0], ['value_amount' => null]);

        $this->connection
            ->update('invoices', ['discount_valueMoney_amount' => 0], ['discount_valueMoney_amount' => null]);

        $this->connection
            ->update('quotes', ['discount_valueMoney_amount' => 0], ['discount_valueMoney_amount' => null]);

        $this->connection
            ->update('recurring_invoices', ['discount_valueMoney_amount' => 0], ['discount_valueMoney_amount' => null]);

        $this->connection
            ->delete('invoice_contact', ['company_id' => null]);

        $this->connection
            ->delete('quote_contact', ['company_id' => null]);

        $this->connection
            ->delete('recurringinvoice_contact', ['company_id' => null]);

        if ($this->platform instanceof MySQLPlatform) {
            $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($this->platform instanceof PostgreSQLPlatform) {
            $this->connection->executeQuery('SET CONSTRAINTS ALL DEFERRED;');
        } elseif ($this->platform instanceof SQLitePlatform) {
            $this->connection->executeQuery('PRAGMA foreign_keys = OFF;');
        } elseif ($this->platform instanceof OraclePlatform) {
            $this->connection->executeQuery('SET CONSTRAINTS ALL DEFERRED;');
        } elseif ($this->platform instanceof SQLServerPlatform) {
            foreach ($schema->getTables() as $table) {
                $this->connection->executeQuery("ALTER TABLE {$table->getName()} NOCHECK CONSTRAINT ALL;");
            }
        }
    }

    public function up(Schema $schema): void
    {
        $recurringInvoices = $schema->getTable('recurring_invoices');
        $invoices = $schema->getTable('invoices');
        $quotes = $schema->getTable('quotes');
        $clientCredit = $schema->getTable('client_credit');
        $invoiceLines = $schema->getTable('invoice_lines');
        $quoteLines = $schema->getTable('quote_lines');
        $contactTypes = $schema->getTable('contact_types');
        $clients = $schema->getTable('clients');
        $recurringInvoiceContact = $schema->getTable('recurringinvoice_contact');
        $invoiceContact = $schema->getTable('invoice_contact');
        $quoteContact = $schema->getTable('quote_contact');
        $users = $schema->getTable('users');
        $userCompany = $schema->getTable('user_company');
        $userInvitations = $schema->getTable('user_invitations');
        $payments = $schema->getTable('payments');

        $recurringInvoices->dropColumn('total_currency');
        $recurringInvoices->dropColumn('baseTotal_currency');
        $recurringInvoices->dropColumn('tax_currency');
        $recurringInvoices->dropColumn('discount_valueMoney_currency');

        $invoices->dropColumn('total_currency');
        $invoices->dropColumn('baseTotal_currency');
        $invoices->dropColumn('balance_currency');
        $invoices->dropColumn('tax_currency');
        $invoices->dropColumn('discount_valueMoney_currency');

        $quotes->dropColumn('total_currency');
        $quotes->dropColumn('baseTotal_currency');
        $quotes->dropColumn('balance_currency');
        $quotes->dropColumn('tax_currency');
        $quotes->dropColumn('discount_valueMoney_currency');

        $clientCredit->dropColumn('value_currency');

        $invoiceLines->dropColumn('price_currency');
        $invoiceLines->dropColumn('total_currency');

        $quoteLines->dropColumn('price_currency');
        $quoteLines->dropColumn('total_currency');

        $this->setColumnType($schema, 'client_credit', 'value_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'recurring_invoices', 'total_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'recurring_invoices', 'baseTotal_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'recurring_invoices', 'tax_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'recurring_invoices', 'discount_valueMoney_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'invoices', 'total_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'invoices', 'baseTotal_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'invoices', 'tax_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'invoices', 'discount_valueMoney_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'invoices', 'balance_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'quotes', 'total_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'quotes', 'baseTotal_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'quotes', 'tax_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'quotes', 'discount_valueMoney_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'invoice_lines', 'price_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'invoice_lines', 'total_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'quote_lines', 'price_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'quote_lines', 'total_amount', BigIntegerType::NAME);
        $this->setColumnType($schema, 'recurringinvoice_contact', 'company_id', UlidType::NAME);
        $this->setColumnType($schema, 'invoice_contact', 'invoice_id', UlidType::NAME);
        $this->setColumnType($schema, 'invoice_contact', 'contact_id', UlidType::NAME);
        $this->setColumnType($schema, 'invoice_contact', 'company_id', UlidType::NAME);
        $this->setColumnType($schema, 'quote_contact', 'quote_id', UlidType::NAME);
        $this->setColumnType($schema, 'quote_contact', 'contact_id', UlidType::NAME);
        $this->setColumnType($schema, 'quote_contact', 'company_id', UlidType::NAME);

        foreach ($clientCredit->getIndexes() as $index) {
            if ($index->getColumns() === ['client_id']) {
                $clientCredit->dropIndex($index->getName());
            }
        }

        $clientCredit->addUniqueIndex(['client_id']);

        $clients->addUniqueIndex(['name', 'company_id']);

        $invoices->addUniqueIndex(['quote_id']);

        $recurringInvoiceContact->addForeignKeyConstraint('companies', ['company_id'], ['id']);

        $invoiceContact->addForeignKeyConstraint('companies', ['company_id'], ['id']);

        $quoteContact->addForeignKeyConstraint('companies', ['company_id'], ['id']);

        $users->addUniqueIndex(['email']);
        $users->dropColumn('username');

        $userCompany->dropPrimaryKey();
        $userCompany->setPrimaryKey(['user_id', 'company_id']);
        $userCompany->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userCompany->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);

        $userInvitations->dropPrimaryKey();
        $userInvitations->setPrimaryKey(['id']);

        $invoices->addColumn('invoice_date', Types::DATE_IMMUTABLE, ['notnull' => false]);

        $transportSettingsTable = $schema->createTable('notification_transport_setting');

        $transportSettingsTable->addColumn('id', UlidType::NAME);
        $transportSettingsTable->addColumn('name', Types::STRING, ['length' => 255]);
        $transportSettingsTable->addColumn('transport', Types::STRING, ['length' => 255]);
        $transportSettingsTable->addColumn('settings', Types::JSON);
        $transportSettingsTable->addColumn('user_id', UlidType::NAME);
        $transportSettingsTable->addColumn('company_id', UlidType::NAME);
        $transportSettingsTable->setPrimaryKey(['id']);
        $transportSettingsTable->addForeignKeyConstraint('users', ['user_id'], ['id']);
        $transportSettingsTable->addForeignKeyConstraint('companies', ['company_id'], ['id']);
        $transportSettingsTable->addUniqueIndex(['name', 'company_id', 'user_id'], 'unique_name_user');

        $userNotificationTable = $schema->createTable('notification_user_setting');
        $userNotificationTable->addColumn('id', UlidType::NAME);
        $userNotificationTable->addColumn('user_id', UlidType::NAME);
        $userNotificationTable->addColumn('company_id', UlidType::NAME);
        $userNotificationTable->addColumn('event', Types::STRING, ['length' => 255]);
        $userNotificationTable->addColumn('email', Types::BOOLEAN);
        $userNotificationTable->setPrimaryKey(['id']);
        $userNotificationTable->addForeignKeyConstraint('users', ['user_id'], ['id']);
        $userNotificationTable->addForeignKeyConstraint('companies', ['company_id'], ['id']);

        $userNotificationTransports = $schema->createTable('usernotification_transportsetting');

        $userNotificationTransports->addColumn('usernotification_id', UlidType::NAME);
        $userNotificationTransports->addColumn('transportsetting_id', UlidType::NAME);
        $userNotificationTransports->setPrimaryKey(['usernotification_id', 'transportsetting_id']);
        $userNotificationTransports->addForeignKeyConstraint('notification_user_setting', ['usernotification_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userNotificationTransports->addForeignKeyConstraint('notification_transport_setting', ['transportsetting_id'], ['id'], ['onDelete' => 'CASCADE']);

        $invoiceLines->addColumn('type', Types::STRING, ['length' => 255, 'notnull' => false]);

        $payments->addColumn('reference', Types::STRING, ['length' => 255, 'notnull' => false]);
        $payments->addColumn('notes', Types::TEXT, ['notnull' => false]);

        $type = Type::getType(UlidType::NAME);

        foreach ($schema->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                if ($column->getType() instanceof UuidBinaryOrderedTimeType) {
                    $column->setType($type);

                    $this->columnsToUpdate[$table->getName()][] = $column->getName();
                }
            }
        }
    }

    public function postUp(Schema $schema): void
    {
        // Add settings to all existing companies
        $companies = $this
            ->connection
            ->createQueryBuilder()
            ->select('id')
            ->from('companies')
            ->executeQuery();

        foreach ($companies->iterateAssociative() as $company) {
            $this->connection
                ->insert(
                    'app_config',
                    [
                        'id' => (new Ulid())->toBinary(),
                        'company_id' => $company['id'],
                        'setting_key' => 'invoice/id_generation/strategy',
                        'setting_value' => 'auto_increment',
                        'description' => '',
                        'field_type' => BillingIdConfigurationType::class,
                    ]
                );

            $this->connection
                ->insert(
                    'app_config',
                    [
                        'id' => (new Ulid())->toBinary(),
                        'company_id' => $company['id'],
                        'setting_key' => 'invoice/id_generation/id_prefix',
                        'setting_value' => '',
                        'description' => 'Example: INV-',
                        'field_type' => TextType::class,
                    ]
                );

            $this->connection
                ->insert(
                    'app_config',
                    [
                        'id' => (new Ulid())->toBinary(),
                        'company_id' => $company['id'],
                        'setting_key' => 'invoice/id_generation/id_suffix',
                        'setting_value' => '',
                        'description' => 'Example: -INV',
                        'field_type' => TextType::class,
                    ]
                );

            $this->connection
                ->insert(
                    'app_config',
                    [
                        'id' => (new Ulid())->toBinary(),
                        'company_id' => $company['id'],
                        'setting_key' => 'quote/id_generation/strategy',
                        'setting_value' => 'auto_increment',
                        'description' => '',
                        'field_type' => BillingIdConfigurationType::class,
                    ]
                );

            $this->connection
                ->insert(
                    'app_config',
                    [
                        'id' => (new Ulid())->toBinary(),
                        'company_id' => $company['id'],
                        'setting_key' => 'quote/id_generation/id_prefix',
                        'setting_value' => '',
                        'description' => 'Example: QUOT-',
                        'field_type' => TextType::class,
                    ]
                );

            $this->connection
                ->insert(
                    'app_config',
                    [
                        'id' => (new Ulid())->toBinary(),
                        'company_id' => $company['id'],
                        'setting_key' => 'quote/id_generation/id_suffix',
                        'setting_value' => '',
                        'description' => 'Example: -QUOT',
                        'field_type' => TextType::class,
                    ]
                );
        }

        // Set invoice date as created date for all existing invoices
        $this->connection
            ->executeStatement('UPDATE invoices SET invoice_date = created');

        $this->connection
            ->update('invoice_lines', ['type' => 'invoice'], ['recurringInvoice_id' => null]);

        $this->connection
            ->update('invoice_lines', ['type' => 'recurring_invoice'], ['invoice_id' => null]);

        if ($this->columnsToUpdate !== [] && Type::hasType(UuidBinaryOrderedTimeType::NAME)) {
            /** @var UuidBinaryOrderedTimeType $type */
            $type = Type::getType(UuidBinaryOrderedTimeType::NAME);

            foreach ($this->columnsToUpdate as $table => $columns) {
                $records = $this->connection->createQueryBuilder()
                    ->select(...$columns)
                    ->from($table)
                    ->fetchAllAssociative();

                foreach ($records as $record) {
                    foreach ($record as $column => $id) {

                        if ($id === null) {
                            continue;
                        }

                        $originalId = $type->convertToPHPValue($id, $this->platform);

                        $convertedId = Ulid::fromRfc4122(UuidV1::fromString($originalId->toString())->toV7()->toRfc4122())->toBinary();

                        $this->connection->update($table, [$column => $convertedId], [$column => $id]);
                    }
                }
            }

            if ($this->platform instanceof MySQLPlatform) {
                $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($this->platform instanceof SQLitePlatform) {
                $this->connection->executeQuery('PRAGMA foreign_keys = ON;');
            } elseif ($this->platform instanceof SQLServerPlatform) {
                foreach ($schema->getTables() as $table) {
                    $this->connection->executeQuery("ALTER TABLE {$table->getName()} CHECK CONSTRAINT ALL;");
                }
            }
        }
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    private function setColumnType(Schema $schema, string $tableName, string $columnName, string $type): void
    {
        $schema->getTable($tableName)
            ->getColumn($columnName)
            ->setType(Type::getType($type))
            ->setNotnull(true);
    }
}
