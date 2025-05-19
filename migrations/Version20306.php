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
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version20306 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySqlPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $resetPasswordRequestTable = $schema->createTable('reset_password_request');
        $userTable = $schema->getTable('users');

        $resetPasswordRequestTable->addColumn('id', UlidType::NAME);
        $resetPasswordRequestTable->addColumn('user_id', UlidType::NAME, ['notnull' => true]);
        $resetPasswordRequestTable->addColumn('selector', Types::STRING, ['length' => 20, 'notnull' => true]);
        $resetPasswordRequestTable->addColumn('hashed_token', Types::STRING, ['length' => 100, 'notnull' => true]);
        $resetPasswordRequestTable->addColumn('requested_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $resetPasswordRequestTable->addColumn('expires_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $resetPasswordRequestTable->setPrimaryKey(['id']);
        $resetPasswordRequestTable->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);

        $userTable->dropColumn('confirmation_token');
        $userTable->dropColumn('password_requested_at');
    }

    public function down(Schema $schema): void
    {
        $resetPasswordRequestTable = $schema->getTable('reset_password_request');
        $userTable = $schema->getTable('users');

        try {
            foreach ($resetPasswordRequestTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getForeignTableName() === 'users') {
                    $resetPasswordRequestTable->removeForeignKey($foreignKey->getName());
                }
            }
        } catch (SchemaException) {
            // ignore
        }

        $userTable->addColumn('confirmation_token', Types::STRING, ['length' => 100, 'notnull' => false]);
        $userTable->addColumn('password_requested_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);

        $schema->dropTable('reset_password_request');
    }
}
