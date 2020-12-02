<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;

final class Version20100 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
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
    }

    public function postUp(Schema $schema): void
    {
        try {
            $this->connection->transactional(function (Connection $connection) {
                $connection->delete('app_config', ['setting_key' => 'email/sending_options/transport']);
                $connection->delete('app_config', ['setting_key' => 'email/sending_options/host']);
                $connection->delete('app_config', ['setting_key' => 'email/sending_options/user']);
                $connection->delete('app_config', ['setting_key' => 'email/sending_options/password']);
                $connection->delete('app_config', ['setting_key' => 'email/sending_options/port']);
                $connection->delete('app_config', ['setting_key' => 'email/sending_options/encryption']);
                $connection->delete('app_config', ['setting_key' => 'email/format']);

                $connection->insert('app_config', ['setting_key' => 'email/sending_options/provider', 'setting_value' => null, 'description' => null, 'field_type' => MailTransportType::class]);
            });
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

    public function isTransactional(): bool
    {
        return false;
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
    }
}
