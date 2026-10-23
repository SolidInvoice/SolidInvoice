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
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Uid\Ulid;

final class Version30200_4 extends AbstractMigration
{
    private const array SETTING_KEYS = [
        'invoice/attachments/max_file_size' => [
            'value' => '10',
            'description' => 'Largest single attachment, in MB.',
        ],
        'invoice/attachments/max_per_invoice' => [
            'value' => '10',
            'description' => 'How many files one invoice may carry.',
        ],
    ];

    public function getDescription(): string
    {
        return 'Create invoice_attachments table for file attachments on invoices, and seed the per-company size and count settings';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('invoice_attachments');

        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('invoice_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('filename', Types::STRING, ['length' => 255, 'notnull' => true]);
        $table->addColumn('mime_type', Types::STRING, ['length' => 127, 'notnull' => true]);
        $table->addColumn('file_size', Types::INTEGER, ['notnull' => true]);
        $table->addColumn('storage_key', Types::STRING, ['length' => 512, 'notnull' => true]);
        $table->addColumn('uploaded_by', UlidType::NAME, ['notnull' => false]);
        $table->addColumn('shared_with_client', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE, ['notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['company_id']);
        $table->addIndex(['invoice_id']);
        $table->addUniqueIndex(['storage_key']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('users', ['uploaded_by'], ['id'], ['onDelete' => 'SET NULL']);
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        foreach ($this->connection->fetchFirstColumn('SELECT id FROM companies') as $companyId) {
            foreach (self::SETTING_KEYS as $settingKey => $setting) {
                $exists = $this->connection->fetchOne(
                    'SELECT 1 FROM app_config WHERE company_id = ? AND setting_key = ?',
                    [$companyId, $settingKey]
                );

                if ($exists !== false) {
                    continue;
                }

                $this->connection->insert(
                    'app_config',
                    [
                        'id' => new Ulid(),
                        'company_id' => $companyId,
                        'setting_key' => $settingKey,
                        'setting_value' => $setting['value'],
                        'description' => $setting['description'],
                        'field_type' => IntegerType::class,
                    ],
                    // Letting DBAL's UlidType convert per-platform, rather than calling
                    // toBinary() directly, which hard-codes the MySQL binary(16) shape and
                    // breaks on PostgreSQL's native uuid column with an invalid-UTF8-byte
                    // error — reproduced against a real PostgreSQL 17 instance.
                    ['id' => UlidType::NAME],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('invoice_attachments');
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        foreach (array_keys(self::SETTING_KEYS) as $settingKey) {
            $this->connection->delete('app_config', ['setting_key' => $settingKey]);
        }
    }
}
