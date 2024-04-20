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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20300 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

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

        $this->renameIndex($schema, 'addresses', 'fk_6fca751619eb6921');
        $this->renameIndex($schema, 'contacts', 'fk_3340157319eb6921');
        $this->renameIndex($schema, 'contact_details', 'fk_e8092a0b5f63ad12');
        $this->renameIndex($schema, 'contact_details', 'fk_e8092a0be7a1254a');
        $this->renameIndex($schema, 'recurring_invoices', 'fk_fe93e28419eb6921');
        $this->renameIndex($schema, 'recurringinvoice_contact', 'fk_1673913ee7a1254a');
        $this->renameIndex($schema, 'recurringinvoice_contact', 'idx_1673913ee31ccdf979b1ad6');
        $this->renameIndex($schema, 'user_invitations', 'fk_8a3cd93ba7b4a7e3');
        $this->renameIndex($schema, 'api_token_history', 'fk_61d8dc4441dee7b9');
        $this->renameIndex($schema, 'api_tokens', 'fk_2cad560ea76ed395');
        $this->renameIndex($schema, 'quote_lines', 'fk_42fe01f7b2a824d8');
        $this->renameIndex($schema, 'quote_lines', 'fk_42fe01f7db805178');
        $this->renameIndex($schema, 'payments', 'fk_65d29b322989f1fd');
        $this->renameIndex($schema, 'payments', 'fk_65d29b32c7440455');
        $this->renameIndex($schema, 'payments', 'fk_65d29b3219883967');
        $this->renameIndex($schema, 'invoice_contact', 'fk_bebbd0ebe7a1254a');
        $this->renameIndex($schema, 'invoice_contact', 'idx_bebbd0eb2989f1fd979b1ad6');
        $this->renameIndex($schema, 'invoices', 'fk_6a2f2f9519eb6921');
        $this->renameIndex($schema, 'invoice_lines', 'fk_72dbdc232989f1fd');
        $this->renameIndex($schema, 'invoice_lines', 'fk_72dbdc23416ccf0f');
        $this->renameIndex($schema, 'invoice_lines', 'fk_72dbdc23b2a824d8');
        $this->renameIndex($schema, 'quotes', 'fk_a1b588c519eb6921');
        $this->renameIndex($schema, 'quote_contact', 'fk_a38d4ebce7a1254a');
        $this->renameIndex($schema, 'quote_contact', 'idx_a38d4ebcdb805178979b1ad6');

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
        $this->setColumnType($schema, 'recurringinvoice_contact', 'company_id', UuidBinaryOrderedTimeType::NAME);
        $this->setColumnType($schema, 'invoice_contact', 'invoice_id', UuidBinaryOrderedTimeType::NAME);
        $this->setColumnType($schema, 'invoice_contact', 'contact_id', UuidBinaryOrderedTimeType::NAME);
        $this->setColumnType($schema, 'invoice_contact', 'company_id', UuidBinaryOrderedTimeType::NAME);
        $this->setColumnType($schema, 'quote_contact', 'quote_id', UuidBinaryOrderedTimeType::NAME);
        $this->setColumnType($schema, 'quote_contact', 'contact_id', UuidBinaryOrderedTimeType::NAME);
        $this->setColumnType($schema, 'quote_contact', 'company_id', UuidBinaryOrderedTimeType::NAME);

        $contactTypes->dropIndex('UNIQ_741A993F5E237E06979B1AD6');
        $contactTypes->addUniqueIndex(['name']);

        $clientCredit->dropIndex('FK_4967254D19EB6921');
        $clientCredit->addUniqueIndex(['client_id']);

        $clients->addUniqueIndex(['name', 'company_id']);

        $invoices->addUniqueIndex(['quote_id']);
        $invoices->addIndex(['quote_id']);

        $recurringInvoiceContact->setPrimaryKey(['recurringinvoice_id', 'contact_id']);
        $recurringInvoiceContact->addForeignKeyConstraint('companies', ['company_id'], ['id']);

        $invoiceContact->addForeignKeyConstraint('companies', ['company_id'], ['id']);
        $invoiceContact->setPrimaryKey(['invoice_id', 'contact_id']);

        $quoteContact->setPrimaryKey(['quote_id', 'contact_id']);
        $quoteContact->addForeignKeyConstraint('companies', ['company_id'], ['id']);

        $users->addUniqueIndex(['email']);
        $users->addUniqueIndex(['username']);

        $userCompany->removeForeignKey('FK_17B21745A76ED395');
        $userCompany->dropPrimaryKey();
        $userCompany->setPrimaryKey(['user_id', 'company_id']);
        $userCompany->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userCompany->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userCompany->addIndex(['user_id']);

        $userInvitations->dropPrimaryKey();
        $userInvitations->setPrimaryKey(['id']);
    }

    /**
     * @throws SchemaException
     */
    private function renameIndex(Schema $schema, string $tableName, string $from): void
    {
        $table = $schema->getTable($tableName);
        $existingIndex = $table->getIndex($from);

        $table->dropIndex($from);
        $table->addIndex($existingIndex->getColumns(), null, $existingIndex->getFlags(), $existingIndex->getOptions());
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
