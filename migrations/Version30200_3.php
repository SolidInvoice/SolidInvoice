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
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version30200_3 extends AbstractMigration
{
    private const string TABLE_NAME = 'reset_password_request';

    private const string SELECTOR_INDEX = 'IDX_7CE748A9692E25D';

    public function getDescription(): string
    {
        return 'Index reset_password_request.selector and cascade its user foreign key, as the platform\'s ResetPasswordRequest entity maps them';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        if (! $table->hasIndex(self::SELECTOR_INDEX)) {
            $table->addIndex(['selector'], self::SELECTOR_INDEX);
        }

        // Version20306 created the foreign key with ON DELETE CASCADE, but the old entity did not
        // map it, so databases built from the ORM metadata by the installer have it without.
        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getReferencedTableName()->getUnqualifiedName()->getValue() !== 'users' || $foreignKey->getOnDeleteAction() === ReferentialAction::CASCADE) {
                continue;
            }

            $name = (string) $foreignKey->getObjectName()?->getIdentifier()->getValue();

            $table->dropForeignKey($name);
            $table->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], $name);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        if ($table->hasIndex(self::SELECTOR_INDEX)) {
            $table->dropIndex(self::SELECTOR_INDEX);
        }

        // The cascade stays: it is what Version20306 created.
    }
}
