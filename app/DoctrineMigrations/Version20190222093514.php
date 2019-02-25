<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version20190222093514 extends AbstractMigration
{
    /**
     * @var Schema
     */
    private $schema;
    
    public function up(Schema $schema): void
    {
        $this->schema = $schema;

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

        $this->createTable(
            'ext_log_entries',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['action', 'string', ['length' => 8, 'notnull' => true]],
                ['logged_at', 'datetime', ['notnull' => true]],
                ['object_id', 'string', ['length' => 64, 'notnull' => false]],
                ['object_class', 'string', ['length' => 255, 'notnull' => true]],
                ['version', 'integer', ['notnull' => true]],
                ['data', 'array', ['notnull' => false]],
                ['username', 'string', ['length' => 255, 'notnull' => false]],
            ]
        )
            ->addIndex(['object_class'], 'log_class_lookup_idx')
            ->addIndex(['logged_at'], 'log_date_lookup_idx')
            ->addIndex(['username'], 'log_user_lookup_idx')
            ->addIndex(['object_id', 'object_class', 'version'], 'log_version_lookup_idx')
            ->addOption('row_format', 'DYNAMIC');

        $this->createTable(
            'addresses',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['client_id', 'integer', ['notnull' => false]],
                ['street1', 'string', ['length' => 255, 'notnull' => false]],
                ['street2', 'string', ['length' => 255, 'notnull' => false]],
                ['city', 'string', ['length' => 255, 'notnull' => false]],
                ['state', 'string', ['length' => 255, 'notnull' => false]],
                ['zip', 'string', ['length' => 255, 'notnull' => false]],
                ['country', 'string', ['length' => 255, 'notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['client_id'])
            ->addForeignKeyConstraint('clients', ['client_id'], ['id']);

        $this->createTable(
            'contact_types',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['name', 'string', ['length' => 45, 'notnull' => true]],
                ['type', 'string', ['length' => 45, 'notnull' => true]],
                ['field_options', 'array', ['notnull' => false]],
                ['required', 'boolean', ['notnull' => true]],
            ]
        )
            ->addUniqueIndex(['name']);

        $this->createTable(
            'contacts',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['client_id', 'integer', ['notnull' => false]],
                ['firstName', 'string', ['length' => 125, 'notnull' => true]],
                ['lastName', 'string', ['length' => 125, 'notnull' => false]],
                ['email', 'string', ['length' => 255, 'notnull' => true]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['client_id'])
            ->addIndex(['email'])
            //->addUniqueIndex(['email'])
            ->addForeignKeyConstraint('clients', ['client_id'], ['id']);

        $this->createTable(
            'client_credit',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['client_id', 'integer', ['notnull' => false]],
                ['value_amount', 'integer', ['notnull' => false]],
                ['value_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            //->addIndex(['client_id'])
            ->addUniqueIndex(['client_id'])
            ->addForeignKeyConstraint('clients', ['client_id'], ['id']);

        $this->createTable(
            'contact_details',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['contact_type_id', 'integer', ['notnull' => false]],
                ['contact_id', 'integer', ['notnull' => false]],
                ['value', 'text', ['notnull' => true]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['contact_type_id'])
            ->addIndex(['contact_id'])
            ->addForeignKeyConstraint('contact_types', ['contact_type_id'], ['id'])
            ->addForeignKeyConstraint('contacts', ['contact_id'], ['id']);

        $this->createTable(
            'clients',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['name', 'string', ['length' => 125, 'notnull' => true]],
                ['website', 'string', ['length' => 125, 'notnull' => false]],
                ['status', 'string', ['length' => 25, 'notnull' => true]],
                ['currency', 'string', ['length' => 3, 'notnull' => false]],
                ['vat_number', 'string', ['length' => 255, 'notnull' => false]],
                ['archived', 'boolean', ['notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addUniqueIndex(['name']);

        $this->createTable(
            'version',
            [
                ['version', 'string', ['length' => 125, 'nonull' => true]]
            ]

        )
            ->setPrimaryKey(['version']);

        $this->createTable(
            'recurring_invoices',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['invoice_id', 'integer', ['notnull' => false]],
                ['frequency', 'string', ['length' => 255, 'notnull' => false]],
                ['date_start', 'date', ['notnull' => true]],
                ['date_end', 'date', ['notnull' => false]],
                ['archived', 'boolean', ['notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addUniqueIndex(['invoice_id'])
            ->addForeignKeyConstraint('invoices', ['invoice_id'], ['id']);

        $this->createTable(
            'invoices',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['client_id', 'integer', ['notnull' => false]],
                ['uuid', 'uuid', ['notnull' => true]],
                ['status', 'string', ['length' => 25, 'notnull' => true]],
                ['terms', 'text', ['notnull' => false]],
                ['notes', 'text', ['notnull' => false]],
                ['due', 'date', ['notnull' => false]],
                ['paid_date', 'datetime', ['notnull' => false]],
                ['is_recurring', 'boolean', ['notnull' => true]],
                ['archived', 'boolean', ['notnull' => false]],
                ['total_amount', 'integer', ['notnull' => false]],
                ['total_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['baseTotal_amount', 'integer', ['notnull' => false]],
                ['baseTotal_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['balance_amount', 'integer', ['notnull' => false]],
                ['balance_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['tax_amount', 'integer', ['notnull' => false]],
                ['tax_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['discount_value_percentage', 'float', ['notnull' => false]],
                ['discount_type', 'string', ['length' => 255, 'notnull' => false]],
                ['discount_valueMoney_amount', 'integer', ['notnull' => false]],
                ['discount_valueMoney_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['client_id'])
            ->addForeignKeyConstraint('clients', ['client_id'], ['id']);

        $this->createTable(
            'invoice_contact',
            [
                ['invoice_id', 'integer', ['notnull' => true]],
                ['contact_id', 'integer', ['notnull' => true]],
            ],
            false
        )
            ->addIndex(['invoice_id'])
            ->addIndex(['contact_id'])
            ->setPrimaryKey(['invoice_id', 'contact_id'])
            ->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'cascade'])
            ->addForeignKeyConstraint('contacts', ['contact_id'], ['id'], ['onDelete' => 'cascade']);

        $this->createTable(
            'invoice_lines',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['invoice_id', 'integer', ['notnull' => false]],
                ['tax_id', 'integer', ['notnull' => false]],
                ['description', 'text', ['notnull' => true]],
                ['qty', 'float', ['notnull' => true]],
                ['price_amount', 'integer', ['notnull' => false]],
                ['price_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['total_amount', 'integer', ['notnull' => false]],
                ['total_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['invoice_id'])
            ->addIndex(['tax_id'])
            ->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'])
            ->addForeignKeyConstraint('tax_rates', ['tax_id'], ['id']);

        $this->createTable(
            'payment_methods',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['name', 'string', ['length' => 125, 'notnull' => true]],
                ['gateway_name', 'string', ['length' => 125, 'notnull' => true]],
                ['factory', 'string', ['length' => 125, 'notnull' => true]],
                ['config', 'array', ['notnull' => false]],
                ['internal', 'boolean', ['notnull' => false]],
                ['enabled', 'boolean', ['notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addUniqueIndex(['gateway_name']);

        $this->createTable(
            'security_token',
            [
                ['hash', 'string', ['length' => 255, 'notnull' => true]],
                ['details', 'object', ['notnull' => false]],
                ['after_url', 'text', ['notnull' => false]],
                ['target_url', 'text', ['notnull' => true]],
                ['gateway_name', 'string', ['length' => 255, 'notnull' => true]],
            ]
        )
            ->setPrimaryKey(['hash']);

        $this->createTable(
            'payments',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['invoice_id', 'integer', ['notnull' => false]],
                ['client', 'integer', ['notnull' => false]],
                ['method_id', 'integer', ['notnull' => false]],
                ['number', 'string', ['length' => 255, 'notnull' => false]],
                ['description', 'string', ['length' => 255, 'notnull' => false]],
                ['client_email', 'string', ['length' => 255, 'notnull' => false]],
                ['client_id', 'string', ['length' => 255, 'notnull' => false]],
                ['total_amount', 'integer', ['notnull' => false]],
                ['currency_code', 'string', ['length' => 255, 'notnull' => false]],
                ['details', 'json_array', ['notnull' => true]],
                ['status', 'string', ['length' => 25, 'notnull' => true]],
                ['message', 'text', ['notnull' => false]],
                ['completed', 'datetime', ['notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['invoice_id'])
            ->addIndex(['client'])
            ->addIndex(['method_id'])
            ->addForeignKeyConstraint('clients', ['client'], ['id'])
            ->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'])
            ->addForeignKeyConstraint('payment_methods', ['method_id'], ['id']);

        $this->createTable(
            'quotes',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['client_id', 'integer', ['notnull' => false]],
                ['uuid', 'uuid', ['notnull' => true]],
                ['status', 'string', ['length' => 25, 'notnull' => true]],
                ['terms', 'text', ['notnull' => false]],
                ['notes', 'text', ['notnull' => false]],
                ['due', 'date', ['notnull' => false]],
                ['archived', 'boolean', ['notnull' => false]],
                ['total_amount', 'integer', ['notnull' => false]],
                ['total_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['baseTotal_amount', 'integer', ['notnull' => false]],
                ['baseTotal_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['tax_amount', 'integer', ['notnull' => false]],
                ['tax_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['discount_value_percentage', 'float', ['notnull' => false]],
                ['discount_type', 'string', ['length' => 255, 'notnull' => false]],
                ['discount_valueMoney_amount', 'integer', ['notnull' => false]],
                ['discount_valueMoney_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['client_id'])
            ->addForeignKeyConstraint('clients', ['client_id'], ['id']);

        $this->createTable(
            'quote_contact',
            [
                ['quote_id', 'integer', ['notnull' => true]],
                ['contact_id', 'integer', ['notnull' => true]],
            ],
            false
        )
            ->addIndex(['quote_id'])
            ->addIndex(['contact_id'])
            ->setPrimaryKey(['quote_id', 'contact_id'])
            ->addForeignKeyConstraint('quotes', ['quote_id'], ['id'], ['onDelete' => 'cascade'])
            ->addForeignKeyConstraint('contacts', ['contact_id'], ['id'], ['onDelete' => 'cascade']);

        $this->createTable(
            'quote_lines',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['quote_id', 'integer', ['notnull' => false]],
                ['tax_id', 'integer', ['notnull' => false]],
                ['description', 'text', ['notnull' => true]],
                ['qty', 'float', ['notnull' => true]],
                ['price_amount', 'integer', ['notnull' => false]],
                ['price_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['total_amount', 'integer', ['notnull' => false]],
                ['total_currency', 'string', ['length' => 3, 'notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['quote_id'])
            ->addIndex(['tax_id'])
            ->addForeignKeyConstraint('quotes', ['quote_id'], ['id'])
            ->addForeignKeyConstraint('tax_rates', ['tax_id'], ['id']);

        $this->createTable(
            'app_config',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['setting_key', 'string', ['length' => 125, 'notnull' => true]],
                ['setting_value', 'text', ['notnull' => false]],
                ['description', 'text', ['notnull' => false]],
                ['field_type', 'string', ['length' => 255, 'notnull' => true]],
            ]
        )
            ->addUniqueIndex(['setting_key']);

        $this->createTable(
            'tax_rates',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['name', 'string', ['length' => 32, 'notnull' => true]],
                ['rate', 'float', ['notnull' => true]],
                ['tax_type', 'string', ['length' => 32, 'notnull' => true]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        );

        $this->createTable(
            'api_tokens',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['user_id', 'integer', ['notnull' => false]],
                ['name', 'string', ['length' => 125, 'notnull' => true]],
                ['token', 'string', ['length' => 125, 'notnull' => true]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['user_id'])
            ->addForeignKeyConstraint('users', ['user_id'], ['id']);

        $this->createTable(
            'api_token_history',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['token_id', 'integer', ['notnull' => false]],
                ['ip', 'string', ['length' => 255, 'notnull' => true]],
                ['resource', 'string', ['length' => 125, 'notnull' => true]],
                ['method', 'string', ['length' => 25, 'notnull' => true]],
                ['requestData', 'array', ['notnull' => true]],
                ['userAgent', 'string', ['length' => 255, 'notnull' => true]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addIndex(['token_id'])
            ->addForeignKeyConstraint('api_tokens', ['token_id'], ['id']);

        $this->createTable(
            'users',
            [
                ['id', 'integer', ['autoincrement' => true, 'notnull' => true]],
                ['username', 'string', ['length' => 180, 'notnull' => true]],
                ['username_canonical', 'string', ['length' => 180, 'notnull' => true]],
                ['email', 'string', ['length' => 180, 'notnull' => true]],
                ['email_canonical', 'string', ['length' => 180, 'notnull' => true]],
                ['enabled', 'boolean', ['notnull' => true]],
                ['salt', 'string', ['length' => 255, 'notnull' => false]],
                ['password', 'string', ['length' => 255, 'notnull' => true]],
                ['last_login', 'datetime', ['notnull' => false]],
                ['confirmation_token', 'string', ['length' => 180, 'notnull' => false]],
                ['password_requested_at', 'datetime', ['notnull' => false]],
                ['roles', 'array', ['notnull' => true]],
                ['mobile', 'string', ['length' => 255, 'notnull' => false]],
                ['deleted', 'datetime', ['notnull' => false]],
                ['created', 'datetime', ['notnull' => true]],
                ['updated', 'datetime', ['notnull' => true]],
            ]
        )
            ->addUniqueIndex(['username_canonical'])
            ->addUniqueIndex(['email_canonical'])
            ->addUniqueIndex(['confirmation_token']);
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'addresses',
            'contact_types',
            'contacts',
            'client_credit',
            'contact_details',
            'clients',
            'version',
            'recurring_invoices',
            'invoices',
            'invoice_contact',
            'invoice_lines',
            'payment_methods',
            'security_token',
            'payments',
            'quotes',
            'quote_contact',
            'quote_lines',
            'app_config',
            'tax_rates',
            'api_tokens',
            'api_token_history',
            'users'
        ];

        foreach ($tables as $table) {
            $t = $schema->getTable($table);
            $fk = $t->getForeignKeys();

            foreach ($fk as $f => $_) {
                $t->removeForeignKey($f);
            }

            $schema->dropTable($table);
        }
    }

    private function createTable($name, array $columns = [], $setPrimaryKey = true): Table
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
