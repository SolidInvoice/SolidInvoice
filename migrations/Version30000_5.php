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

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version30000_5 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE/SET NULL to FK constraints for correct cascade deletion';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        // company_id FKs: add CASCADE to all company-aware tables
        $companyAwareTables = [
            'invoice_reminders',
            'tax_rates',
            'notification_transport_setting',
            'notification_user_setting',
            'app_config',
            'clients',
            'contact_details',
            'client_credit',
            'contacts',
            'contact_types',
            'addresses',
            'payments',
            'payment_methods',
            'user_invitations',
            'quote_lines',
            'quotes',
            'api_token_history',
            'api_tokens',
            'invoice_lines',
            'invoices',
            'recurring_invoices',
        ];

        foreach ($companyAwareTables as $tableName) {
            $this->updateForeignKey($schema->getTable($tableName), 'companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        // user_company join table: company_id → companies.id CASCADE
        $this->updateForeignKey($schema->getTable('user_company'), 'companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        // invoice_contact join table
        $this->updateForeignKey($schema->getTable('invoice_contact'), 'invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);
        $this->updateForeignKey($schema->getTable('invoice_contact'), 'contacts', ['contact_id'], ['id'], ['onDelete' => 'CASCADE']);

        // quote_contact join table
        $this->updateForeignKey($schema->getTable('quote_contact'), 'quotes', ['quote_id'], ['id'], ['onDelete' => 'CASCADE']);
        $this->updateForeignKey($schema->getTable('quote_contact'), 'contacts', ['contact_id'], ['id'], ['onDelete' => 'CASCADE']);

        // recurringinvoice_contacts join table (column is 'recurringinvoice_id', not 'recurring_invoice_id')
        $this->updateForeignKey($schema->getTable('recurringinvoice_contacts'), 'recurring_invoices', ['recurringinvoice_id'], ['id'], ['onDelete' => 'CASCADE']);
        $this->updateForeignKey($schema->getTable('recurringinvoice_contacts'), 'contacts', ['contact_id'], ['id'], ['onDelete' => 'CASCADE']);

        // recurring_options: recurringInvoice_id → recurring_invoices.id CASCADE (column is camelCase)
        $this->updateForeignKey($schema->getTable('recurring_options'), 'recurring_invoices', ['recurringInvoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        // invoices: client_id → clients.id CASCADE
        $this->updateForeignKey($schema->getTable('invoices'), 'clients', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);

        // invoices: quote_id → quotes.id SET NULL
        $this->updateForeignKey($schema->getTable('invoices'), 'quotes', ['quote_id'], ['id'], ['onDelete' => 'SET NULL']);

        // invoices: recurring_invoice_id → recurring_invoices.id SET NULL
        $this->updateForeignKey($schema->getTable('invoices'), 'recurring_invoices', ['recurring_invoice_id'], ['id'], ['onDelete' => 'SET NULL']);

        // invoice_lines: invoice_id → invoices.id CASCADE
        $this->updateForeignKey($schema->getTable('invoice_lines'), 'invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        // invoice_lines: recurringInvoice_id → recurring_invoices.id CASCADE (RecurringInvoiceLine SINGLE_TABLE, column is camelCase)
        $this->updateForeignKey($schema->getTable('invoice_lines'), 'recurring_invoices', ['recurringInvoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        // quote_lines: quote_id → quotes.id CASCADE
        $this->updateForeignKey($schema->getTable('quote_lines'), 'quotes', ['quote_id'], ['id'], ['onDelete' => 'CASCADE']);

        // payments: invoice_id → invoices.id CASCADE
        $this->updateForeignKey($schema->getTable('payments'), 'invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        // payments: client → clients.id SET NULL (column is named 'client' not 'client_id')
        $this->updateForeignKey($schema->getTable('payments'), 'clients', ['client'], ['id'], ['onDelete' => 'SET NULL']);

        // contacts: client_id → clients.id CASCADE
        $this->updateForeignKey($schema->getTable('contacts'), 'clients', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);

        // addresses: client_id → clients.id CASCADE
        $this->updateForeignKey($schema->getTable('addresses'), 'clients', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        // Revert company_id FKs to no onDelete option
        $companyAwareTables = [
            'invoice_reminders',
            'tax_rates',
            'notification_transport_setting',
            'notification_user_setting',
            'app_config',
            'clients',
            'contact_details',
            'client_credit',
            'contacts',
            'contact_types',
            'addresses',
            'payments',
            'payment_methods',
            'user_invitations',
            'quote_lines',
            'quotes',
            'api_token_history',
            'api_tokens',
            'invoice_lines',
            'invoices',
            'recurring_invoices',
        ];

        foreach ($companyAwareTables as $tableName) {
            $this->updateForeignKey($schema->getTable($tableName), 'companies', ['company_id'], ['id'], []);
        }

        $this->updateForeignKey($schema->getTable('user_company'), 'companies', ['company_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('invoice_contact'), 'invoices', ['invoice_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('invoice_contact'), 'contacts', ['contact_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('quote_contact'), 'quotes', ['quote_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('quote_contact'), 'contacts', ['contact_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('recurringinvoice_contacts'), 'recurring_invoices', ['recurringinvoice_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('recurringinvoice_contacts'), 'contacts', ['contact_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('recurring_options'), 'recurring_invoices', ['recurringInvoice_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('invoices'), 'clients', ['client_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('invoices'), 'quotes', ['quote_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('invoices'), 'recurring_invoices', ['recurring_invoice_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('invoice_lines'), 'invoices', ['invoice_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('invoice_lines'), 'recurring_invoices', ['recurringInvoice_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('quote_lines'), 'quotes', ['quote_id'], ['id'], []);

        $this->updateForeignKey($schema->getTable('payments'), 'invoices', ['invoice_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('payments'), 'clients', ['client'], ['id'], []);

        $this->updateForeignKey($schema->getTable('contacts'), 'clients', ['client_id'], ['id'], []);
        $this->updateForeignKey($schema->getTable('addresses'), 'clients', ['client_id'], ['id'], []);
    }

    /**
     * @param string[] $localColumns
     * @param string[] $foreignColumns
     * @param array<string, string> $options
     */
    private function updateForeignKey(Table $table, string $foreignTable, array $localColumns, array $foreignColumns, array $options): void
    {
        foreach ($table->getForeignKeys() as $fk) {
            if ($fk->getForeignTableName() === $foreignTable) {
                $table->removeForeignKey($fk->getName());
                break;
            }
        }

        $table->addForeignKeyConstraint($foreignTable, $localColumns, $foreignColumns, $options);
    }
}
