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

final class Version30000_8 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create export_jobs table for tracking full company data export requests';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('export_jobs');

        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('requested_by', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('format', Types::STRING, ['length' => 10, 'notnull' => true]);
        $table->addColumn('status', Types::STRING, ['length' => 20, 'notnull' => true]);
        $table->addColumn('archive_path', Types::STRING, ['length' => 512, 'notnull' => false]);
        $table->addColumn('file_size', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('completed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('failure_reason', Types::TEXT, ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['company_id']);
        $table->addIndex(['requested_by']);
        $table->addIndex(['status']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('users', ['requested_by'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('export_jobs');
    }
}
