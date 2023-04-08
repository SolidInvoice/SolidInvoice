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
use Doctrine\Migrations\AbstractMigration;

final class Version20201 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
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

        $schema->dropTable('ext_log_entries');
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
}
