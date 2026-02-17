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
use Symfony\Bridge\Doctrine\Types\UlidType;
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

        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('invoice_id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME);
        $table->addColumn('reminder_type', Types::STRING, ['length' => 20]);
        $table->addColumn('sent_at', Types::DATETIME_IMMUTABLE, [ 'notnull' => false]);
        $table->addColumn('status', Types::STRING, ['length' => 20, 'notnull' => true]);
        $table->addColumn('failure_reason', Types::TEXT, ['notnull' => false]);
        $table->addColumn('created', Types::DATETIME_MUTABLE);
        $table->addColumn('updated', Types::DATETIME_MUTABLE);

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
                // Check if the setting already exists for this company
                $exists = $this->connection->fetchOne(
                    'SELECT 1 FROM app_config WHERE company_id = ? AND setting_key = ?',
                    [$companyId, $setting['key']]
                );

                // Only insert if it doesn't exist
                if ($exists === false) {
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
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        // Get all existing companies
        $companies = $this->connection->fetchAllAssociative('SELECT id FROM companies');

        $settingKeys = [
            'invoice/reminder/enabled',
            'invoice/reminder/pre_due_enabled',
            'invoice/reminder/pre_due_days',
        ];

        // Delete settings for each company to ensure idempotent down->up cycles
        foreach ($companies as $company) {
            $companyId = $company['id'];

            foreach ($settingKeys as $key) {
                $this->connection->delete('app_config', [
                    'company_id' => $companyId,
                    'setting_key' => $key,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('invoice_reminders');
    }
}
