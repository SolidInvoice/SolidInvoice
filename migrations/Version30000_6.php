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
        return 'Create api_webhooks table for outgoing webhook subscriptions';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('api_webhooks');

        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('url', Types::STRING, ['length' => 2048, 'notnull' => true]);
        $table->addColumn('events', Types::JSON, ['notnull' => true]);
        $table->addColumn('secret', Types::STRING, ['length' => 64, 'notnull' => true]);
        $table->addColumn('active', Types::BOOLEAN, ['notnull' => true, 'default' => true]);
        $table->addColumn('created', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $table->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('api_webhooks');
    }
}
