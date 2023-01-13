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
use Doctrine\DBAL\Types\JsonType;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use function array_map;

final class Version20200 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
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

        $companiesTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $companiesTable->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $companiesTable->setPrimaryKey(['id']);

        $userCompaniesTable->addColumn('user_id', 'integer', ['notnull' => true]);
        $userCompaniesTable->addColumn('company_id', 'integer', ['notnull' => true]);
        $userCompaniesTable->setPrimaryKey(['user_id', 'company_id']);
        $userCompaniesTable->addIndex(['user_id']);
        $userCompaniesTable->addIndex(['company_id']);
        // $userCompaniesTable->addForeignKeyConstraint($companiesTable, ['company_id'], ['id']);
        // $userCompaniesTable->addForeignKeyConstraint($schema->getTable('users'), ['user_id'], ['id']);

        $appConfigTable = $schema->getTable('app_config');

        try {
            $appConfigTable->dropIndex('UNIQ_318942FC5FA1E697');
        } catch (SchemaException $exception) {
        }

        $appConfigTable->addColumn('company_id', 'integer');
        $appConfigTable->addIndex(['company_id']);
        $appConfigTable->addUniqueIndex(['setting_key', 'company_id']);
        // $appConfigTable->addForeignKeyConstraint($companiesTable, ['company_id'], ['id']);
        $appConfigTable->addUniqueIndex(['company_id', 'setting_key']);

        $addCompanyToTable = static function (string $tableName) use ($schema): Table {
            $table = $schema->getTable($tableName);

            $table->addColumn('company_id', 'integer');
            $table->addIndex(['company_id']);
            // $table->addForeignKeyConstraint($companiesTable, ['company_id'], ['id']);

            return $table;
        };

        $clientsTable = $addCompanyToTable('clients');

        try {
            $clientsTable->dropIndex('UNIQ_C82E745E237E06');
        } catch (SchemaException $exception) {
        }

        $clientsTable->addUniqueConstraint(['company_id', 'name']);

        $addCompanyToTable('invoices');
        $addCompanyToTable('payment_methods');
        $addCompanyToTable('payments');
        $addCompanyToTable('quotes');
        $addCompanyToTable('recurring_invoices');
        $addCompanyToTable('tax_rates');
    }

    public function postUp(Schema $schema): void
    {
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

        $this->connection
            ->insert('companies', ['name' => $companyName]);

        foreach (['app_config', 'clients', 'invoices', 'payment_methods', 'payments', 'quotes', 'recurring_invoices', 'tax_rates'] as $table) {
            $this->connection->update($table, ['company_id' => 1], ['1' => '1']);
        }

        foreach ($users as $user) {
            $this->connection
                ->insert('user_company', [
                    'user_id' => $user['id'],
                    'company_id' => 1,
                ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => 'invoice/watermark']);
        $this->connection->delete('app_config', ['setting_key' => 'quote/watermark']);

        $this->addSql('ALTER TABLE app_config DROP FOREIGN KEY FK_318942FC979B1AD6');
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E74979B1AD6');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95979B1AD6');
        $this->addSql('ALTER TABLE payment_methods DROP FOREIGN KEY FK_4FABF983979B1AD6');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32979B1AD6');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_A1B588C5979B1AD6');
        $this->addSql('ALTER TABLE recurring_invoices DROP FOREIGN KEY FK_FE93E284979B1AD6');
        $this->addSql('ALTER TABLE tax_rates DROP FOREIGN KEY FK_F7AE5E1D979B1AD6');
        $this->addSql('ALTER TABLE user_company DROP FOREIGN KEY FK_17B21745A76ED395');
        $this->addSql('ALTER TABLE user_company DROP FOREIGN KEY FK_17B21745979B1AD6');
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP TABLE user_company');
        $this->addSql('DROP INDEX IDX_318942FC979B1AD6 ON app_config');
        $this->addSql('DROP INDEX UNIQ_318942FC5FA1E697979B1AD6 ON app_config');
        $this->addSql('ALTER TABLE app_config DROP company_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_318942FC5FA1E697 ON app_config (setting_key)');
        $this->addSql('DROP INDEX IDX_C82E74979B1AD6 ON clients');
        $this->addSql('DROP INDEX UNIQ_C82E745E237E06979B1AD6 ON clients');
        $this->addSql('ALTER TABLE clients DROP company_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C82E745E237E06 ON clients (name)');
        $this->addSql('DROP INDEX IDX_6A2F2F95979B1AD6 ON invoices');
        $this->addSql('ALTER TABLE invoices DROP company_id');
        $this->addSql('DROP INDEX IDX_4FABF983979B1AD6 ON payment_methods');
        $this->addSql('ALTER TABLE payment_methods DROP company_id');
        $this->addSql('DROP INDEX IDX_65D29B32979B1AD6 ON payments');
        $this->addSql('ALTER TABLE payments DROP company_id');
        $this->addSql('DROP INDEX IDX_A1B588C5979B1AD6 ON quotes');
        $this->addSql('ALTER TABLE quotes DROP company_id');
        $this->addSql('DROP INDEX IDX_FE93E284979B1AD6 ON recurring_invoices');
        $this->addSql('ALTER TABLE recurring_invoices DROP company_id');
        $this->addSql('DROP INDEX IDX_F7AE5E1D979B1AD6 ON tax_rates');
        $this->addSql('ALTER TABLE tax_rates DROP company_id');
    }
}
