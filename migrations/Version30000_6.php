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
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30000_6 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add time tracking tables and line item type columns for invoice/quote lines';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        // Add line_item_type to invoice_lines
        $invoiceLinesTable = $schema->getTable('invoice_lines');
        $invoiceLinesTable->addColumn('line_item_type', Types::STRING, ['length' => 50, 'notnull' => true, 'default' => 'standard']);

        // Add line_item_type to quote_lines
        $quoteLinesTable = $schema->getTable('quote_lines');
        $quoteLinesTable->addColumn('line_item_type', Types::STRING, ['length' => 50, 'notnull' => true, 'default' => 'standard']);

        // Add hourly_rate to clients
        $clientsTable = $schema->getTable('clients');
        $clientsTable->addColumn('hourly_rate', Types::BIGINT, ['notnull' => false]);

        // Create time_tracking_timers table
        $timersTable = $schema->createTable('time_tracking_timers');
        $timersTable->addColumn('id', UlidType::NAME);
        $timersTable->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $timersTable->addColumn('user_id', UlidType::NAME, ['notnull' => true]);
        $timersTable->addColumn('client_id', UlidType::NAME, ['notnull' => false]);
        $timersTable->addColumn('description', Types::TEXT, ['notnull' => false]);
        $timersTable->addColumn('status', Types::STRING, ['length' => 25, 'notnull' => true, 'default' => 'running']);
        $timersTable->addColumn('started_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $timersTable->addColumn('last_started_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $timersTable->addColumn('elapsed_seconds', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $timersTable->addColumn('created', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $timersTable->addColumn('updated', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $timersTable->setPrimaryKey(['id']);
        $timersTable->addIndex(['company_id']);
        $timersTable->addIndex(['user_id']);
        $timersTable->addIndex(['client_id']);
        $timersTable->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $timersTable->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $timersTable->addForeignKeyConstraint('clients', ['client_id'], ['id'], ['onDelete' => 'SET NULL']);

        // Create time_entries table
        $entriesTable = $schema->createTable('time_entries');
        $entriesTable->addColumn('id', UlidType::NAME);
        $entriesTable->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $entriesTable->addColumn('user_id', UlidType::NAME, ['notnull' => true]);
        $entriesTable->addColumn('client_id', UlidType::NAME, ['notnull' => true]);
        $entriesTable->addColumn('invoice_id', UlidType::NAME, ['notnull' => false]);
        $entriesTable->addColumn('timer_id', UlidType::NAME, ['notnull' => false]);
        $entriesTable->addColumn('description', Types::TEXT, ['notnull' => false]);
        $entriesTable->addColumn('entry_date', Types::DATE_MUTABLE, ['notnull' => true]);
        $entriesTable->addColumn('duration', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $entriesTable->addColumn('hourly_rate', Types::BIGINT, ['notnull' => true, 'default' => 0]);
        $entriesTable->addColumn('status', Types::STRING, ['length' => 25, 'notnull' => true, 'default' => 'pending']);
        $entriesTable->addColumn('created', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $entriesTable->addColumn('updated', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $entriesTable->setPrimaryKey(['id']);
        $entriesTable->addIndex(['company_id']);
        $entriesTable->addIndex(['user_id']);
        $entriesTable->addIndex(['client_id']);
        $entriesTable->addIndex(['invoice_id']);
        $entriesTable->addIndex(['timer_id']);
        $entriesTable->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $entriesTable->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $entriesTable->addForeignKeyConstraint('clients', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);
        $entriesTable->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'SET NULL']);
        $entriesTable->addForeignKeyConstraint('time_tracking_timers', ['timer_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        // Drop new tables (entries first due to FK dependency on timers)
        $schema->dropTable('time_entries');
        $schema->dropTable('time_tracking_timers');

        // Remove hourly_rate from clients
        $clientsTable = $schema->getTable('clients');
        $clientsTable->dropColumn('hourly_rate');

        // Remove line_item_type from quote_lines
        $quoteLinesTable = $schema->getTable('quote_lines');
        $quoteLinesTable->dropColumn('line_item_type');

        // Remove line_item_type from invoice_lines
        $invoiceLinesTable = $schema->getTable('invoice_lines');
        $invoiceLinesTable->dropColumn('line_item_type');
    }
}
