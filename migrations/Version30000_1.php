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

final class Version30000_1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_settings table for storing user-specific settings';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $userSettingsTable = $schema->createTable('user_settings');

        $userSettingsTable->addColumn('id', UlidType::NAME);
        $userSettingsTable->addColumn('user_id', UlidType::NAME, ['notnull' => true]);
        $userSettingsTable->addColumn('setting_key', Types::STRING, ['length' => 125, 'notnull' => true]);
        $userSettingsTable->addColumn('setting_value', Types::TEXT, ['notnull' => false]);
        $userSettingsTable->addColumn('created', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $userSettingsTable->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => true]);

        $userSettingsTable->setPrimaryKey(['id']);
        $userSettingsTable->addUniqueIndex(['setting_key', 'user_id']);
        $userSettingsTable->addForeignKeyConstraint('users', ['user_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('user_settings');
    }
}
