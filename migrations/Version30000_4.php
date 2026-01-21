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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Uid\Ulid;

final class Version30000_4 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invoice_reminders table and add default reminder settings for all companies';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('invoice_reminders');

        $table->addColumn('id', Types::BINARY, ['length' => 16, 'fixed' => true]);
        $table->addColumn('invoice_id', Types::BINARY, ['length' => 16, 'fixed' => true]);
        $table->addColumn('company_id', Types::BINARY, ['length' => 16, 'fixed' => true]);
        $table->addColumn('reminder_type', Types::STRING, ['length' => 20]);
        $table->addColumn('sent_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['company_id', 'invoice_id', 'reminder_type']);

        $table->addForeignKeyConstraint(
            'invoices',
            ['invoice_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
        );

        $table->addForeignKeyConstraint(
            'companies',
            ['company_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
        );
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        // Get all existing companies
        $companies = $this->connection->fetchAllAssociative('SELECT id FROM companies');

        $settings = [
            [
                'key' => 'invoice/reminder/enabled',
                'value' => '1',
                'description' => 'Enable automatic invoice payment reminders',
                'type' => CheckboxType::class,
            ],
            [
                'key' => 'invoice/reminder/pre_due_enabled',
                'value' => '1',
                'description' => 'Send reminder before invoice is due',
                'type' => CheckboxType::class,
            ],
            [
                'key' => 'invoice/reminder/pre_due_days',
                'value' => '3',
                'description' => 'Days before due date to send pre-due reminder (0 to disable)',
                'type' => IntegerType::class,
            ],
        ];

        // Insert settings for each company
        foreach ($companies as $company) {
            $companyId = $company['id'];

            foreach ($settings as $setting) {
                $this->connection->insert('app_config', [
                    'id' => (new Ulid())->toBinary(),
                    'company_id' => $companyId,
                    'setting_key' => $setting['key'],
                    'setting_value' => $setting['value'],
                    'description' => $setting['description'],
                    'field_type' => $setting['type'],
                ]);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => 'invoice/reminder/enabled']);
        $this->connection->delete('app_config', ['setting_key' => 'invoice/reminder/pre_due_enabled']);
        $this->connection->delete('app_config', ['setting_key' => 'invoice/reminder/pre_due_days']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('invoice_reminders');
    }
}
