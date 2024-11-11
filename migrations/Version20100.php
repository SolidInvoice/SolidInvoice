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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;

final class Version20100 extends AbstractMigration
{
    private Schema $schema;

    public function up(Schema $schema): void
    {
        $this->schema = $schema;
        $schema->dropTable('ext_translations');

        $schema->getTable('addresses')->dropColumn('deleted');
        $schema->getTable('contacts')->dropColumn('deleted');
        $schema->getTable('client_credit')->dropColumn('deleted');
        $schema->getTable('clients')->dropColumn('deleted');
        $schema->getTable('invoices')->dropColumn('deleted');
        $schema->getTable('recurring_invoices')->dropColumn('deleted');
        $schema->getTable('invoice_lines')->dropColumn('deleted');
        $schema->getTable('payment_methods')->dropColumn('deleted');
        $schema->getTable('payments')->dropColumn('deleted');
        $schema->getTable('quotes')->dropColumn('deleted');
        $schema->getTable('quote_lines')->dropColumn('deleted');
        $schema->getTable('tax_rates')->dropColumn('deleted');

        $userTable = $schema->getTable('users');

        try {
            $userTable->dropIndex('UNIQ_1483A5E9A0D96FBF');
        } catch (SchemaException $exception) {
        }

        try {
            $userTable->dropIndex('UNIQ_1483A5E992FC23A8');
        } catch (SchemaException $exception) {
        }

        $userTable->dropColumn('username_canonical')
            ->dropColumn('email_canonical')
            ->dropColumn('salt')
            ->dropColumn('deleted');

        $schema->getTable('invoices')->addIndex(['quote_id']);

        $this->createTable(
            'recurringinvoice_contact',
            [
                ['recurringinvoice_id', 'integer', ['notnull' => true]],
                ['contact_id', 'integer', ['notnull' => true]],
            ]
        )
            ->addIndex(['recurringinvoice_id'])
            ->addIndex(['contact_id'])
            ->setPrimaryKey(['recurringinvoice_id', 'contact_id'])
            ->addForeignKeyConstraint('recurring_invoices', ['recurringinvoice_id'], ['id'], ['onDelete' => 'cascade'])
            ->addForeignKeyConstraint('contacts', ['contact_id'], ['id'], ['onDelete' => 'cascade']);

        $invoiceLinesTable = $schema->getTable('invoice_lines');
        $invoiceLinesTable->addColumn('recurringInvoice_id', 'integer', ['notnull' => false]);
        $invoiceLinesTable->addForeignKeyConstraint('recurring_invoices', ['recurringInvoice_id'], ['id']);
        $invoiceLinesTable->addIndex(['recurringInvoice_id']);

        $recurringInvoices = $schema->getTable('recurring_invoices');

        if ($recurringInvoices->hasForeignKey('FK_FE93E2842989F1FD')) {
            $recurringInvoices->removeForeignKey('FK_FE93E2842989F1FD');
        }

        $recurringInvoices->dropIndex('UNIQ_FE93E2842989F1FD');
        $recurringInvoices->addColumn('status', 'string', ['length' => 25]);
        $recurringInvoices->addColumn('terms', 'text', ['notnull' => false]);
        $recurringInvoices->addColumn('notes', 'text', ['notnull' => false]);
        $recurringInvoices->addColumn('total_amount', 'integer', ['notnull' => false]);
        $recurringInvoices->addColumn('total_currency', 'string', ['length' => 3, 'notnull' => false]);
        $recurringInvoices->addColumn('baseTotal_amount', 'integer', ['notnull' => false]);
        $recurringInvoices->addColumn('baseTotal_currency', 'string', ['length' => 3, 'notnull' => false]);
        $recurringInvoices->addColumn('tax_amount', 'integer', ['notnull' => false]);
        $recurringInvoices->addColumn('tax_currency', 'string', ['length' => 3, 'notnull' => false]);
        $recurringInvoices->addColumn('discount_value_percentage', 'float', ['notnull' => false]);
        $recurringInvoices->addColumn('discount_type', 'string', ['length' => 255, 'notnull' => false]);
        $recurringInvoices->addColumn('discount_valueMoney_amount', 'integer', ['notnull' => false]);
        $recurringInvoices->addColumn('discount_valueMoney_currency', 'string', ['length' => 3, 'notnull' => false]);
        $recurringInvoices->addColumn('client_id', 'integer', ['notnull' => false]);
        $recurringInvoices->dropColumn('invoice_id');
        $recurringInvoices->getColumn('date_start')->setType(Type::getType(Types::DATE_IMMUTABLE));
        $recurringInvoices->getColumn('date_end')->setType(Type::getType(Types::DATE_IMMUTABLE));
        $recurringInvoices->addForeignKeyConstraint('clients', ['client_id'], ['id']);
        $recurringInvoices->addIndex(['client_id']);

        $schema->getTable('invoices')
            ->dropColumn('is_recurring');
    }

    public function postUp(Schema $schema): void
    {
        try {
            $this->connection->delete('app_config', ['setting_key' => 'email/sending_options/transport']);
            $this->connection->delete('app_config', ['setting_key' => 'email/sending_options/host']);
            $this->connection->delete('app_config', ['setting_key' => 'email/sending_options/user']);
            $this->connection->delete('app_config', ['setting_key' => 'email/sending_options/password']);
            $this->connection->delete('app_config', ['setting_key' => 'email/sending_options/port']);
            $this->connection->delete('app_config', ['setting_key' => 'email/sending_options/encryption']);
            $this->connection->delete('app_config', ['setting_key' => 'email/format']);
            $this->connection->insert('app_config', ['setting_key' => 'email/sending_options/provider', 'setting_value' => null, 'description' => null, 'field_type' => MailTransportType::class]);
        } catch (\Throwable $e) {
            $this->write(sprintf('Unable to load data: %s. Rolling back migration', $e->getMessage()));

            try {
                $this->down($schema);
            } catch (\Throwable $e) {
                $this->write(sprintf('Unable to roll back migration: %s. ', $e->getMessage()));
            }

            $this->abortIf(true, $e->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('addresses')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('contacts')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('client_credit')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('clients')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('invoices')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('recurring_invoices')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('invoice_lines')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('payment_methods')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('payments')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('quotes')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('quote_lines')->addColumn('deleted', 'datetime', ['notnull' => false]);
        $schema->getTable('tax_rates')->addColumn('deleted', 'datetime', ['notnull' => false]);

        try {
            $schema->getTable('invoices')->dropIndex('IDX_6A2F2F95DB805178');
        } catch (SchemaException $exception) {
        }

        $usersTable = $schema->getTable('users');
        $usersTable->addColumn('username_canonical', 'string', ['length' => 180, 'notnull' => true]);
        $usersTable->addColumn('email_canonical', 'string', ['length' => 180, 'notnull' => true]);

        $usersTable
            ->addUniqueIndex(['username_canonical'])
            ->addUniqueIndex(['email_canonical']);

        $this->createTable(
            'ext_translations',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['locale', 'string', ['length' => 8, 'notnull' => true]],
                ['object_class', 'string', ['length' => 255, 'notnull' => true]],
                ['field', 'string', ['length' => 32, 'notnull' => true]],
                ['foreign_key', 'string', ['length' => 64, 'notnull' => true]],
                ['content', 'text', ['notnull' => false]],
            ]
        )
            ->addIndex(['locale', 'object_class', 'foreign_key'], 'translations_lookup_idx')
            ->addUniqueIndex(['locale', 'object_class', 'field', 'foreign_key'], 'lookup_unique_idx')
            ->addOption('row_format', 'DYNAMIC');

        $schema->dropTable('recurringinvoice_contact');
        $schema->getTable('invoice_lines')->removeForeignKey('FK_72DBDC23416CCF0F');
        $schema->getTable('invoice_lines')->dropIndex('IDX_72DBDC23416CCF0F');
        $schema->getTable('invoice_lines')->dropColumn('recurringInvoice_id');
        $schema->getTable('invoices')->addColumn('is_recurring', 'boolean', ['notnull' => true]);
        $recurringInvoices = $schema->getTable('recurring_invoices');

        $recurringInvoices->removeForeignKey('FK_FE93E28419EB6921');
        $recurringInvoices->dropIndex('IDX_FE93E28419EB6921');
        $recurringInvoices->dropColumn('client_id');
        $recurringInvoices->dropColumn('status');
        $recurringInvoices->dropColumn('terms');
        $recurringInvoices->dropColumn('notes');
        $recurringInvoices->dropColumn('total_amount');
        $recurringInvoices->dropColumn('total_currency');
        $recurringInvoices->dropColumn('baseTotal_amount');
        $recurringInvoices->dropColumn('baseTotal_currency');
        $recurringInvoices->dropColumn('tax_amount');
        $recurringInvoices->dropColumn('tax_currency');
        $recurringInvoices->dropColumn('discount_value_percentage');
        $recurringInvoices->dropColumn('discount_type');
        $recurringInvoices->dropColumn('discount_valueMoney_amount');
        $recurringInvoices->dropColumn('discount_valueMoney_currency');
        $recurringInvoices->addColumn('invoice_id', 'integer', ['integer', ['notnull' => false]]);
        $recurringInvoices->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onupdate' => 'NO ACTION', 'ondelete' => 'NO ACTION']);
        $recurringInvoices->addUniqueIndex(['invoice_id']);
    }

    /**
     * @param list<mixed> $columns
     * @throws SchemaException
     */
    private function createTable(string $name, array $columns = [], bool $setPrimaryKey = true): Table
    {
        $table = $this->schema->createTable($name);

        $hasId = false;
        foreach ($columns as $column) {
            if ('id' === $column[0]) {
                $hasId = true;
            }

            $table->addColumn(...$column);
        }

        if ($setPrimaryKey && $hasId) {
            $table->setPrimaryKey(['id']);
        }

        return $table;
    }
}
