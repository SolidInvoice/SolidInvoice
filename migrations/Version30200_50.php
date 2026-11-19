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

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30200_50 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the processed_messages table for the messenger monitor bundle, missing since its introduction';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('processed_messages')) {
            return;
        }

        $table = $schema->createTable('processed_messages');

        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('run_id', Types::INTEGER);
        $table->addColumn('attempt', Types::SMALLINT);
        $table->addColumn('message_type', Types::STRING, ['length' => 255]);
        $table->addColumn('input', Types::JSON, ['notnull' => false]);
        $table->addColumn('description', Types::TEXT, ['notnull' => false]);
        $table->addColumn('dispatched_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('received_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('finished_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('wait_time', Types::BIGINT);
        $table->addColumn('handle_time', Types::BIGINT);
        $table->addColumn('memory_usage', Types::BIGINT);
        $table->addColumn('transport', Types::STRING, ['length' => 255]);
        $table->addColumn('tags', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('failure_type', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('failure_message', Types::TEXT, ['notnull' => false]);
        $table->addColumn('results', Types::JSON, ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['finished_at'], 'idx_processed_messages_finished_at');
        $table->addIndex(['run_id'], 'idx_processed_messages_run_id');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('processed_messages')) {
            $schema->dropTable('processed_messages');
        }
    }
}
