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
}
