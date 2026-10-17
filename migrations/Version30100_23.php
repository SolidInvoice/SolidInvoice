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
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version30100_23 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename the reset_password_request and user_invitations columns the ORM mapping already renamed, instead of letting the schema diff drop and recreate them';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $this->renameIfPresent($schema->getTable('reset_password_request'), 'hashed_token', 'hashedToken');
        $this->renameIfPresent($schema->getTable('reset_password_request'), 'requested_at', 'requestedAt');
        $this->renameIfPresent($schema->getTable('reset_password_request'), 'expires_at', 'expiresAt');

        $this->renameIfPresent($schema->getTable('user_invitations'), 'expires_at', 'expiresAt');
        $this->renameIfPresent($schema->getTable('user_invitations'), 'reminder_sent_at', 'reminderSentAt');
    }

    public function down(Schema $schema): void
    {
        $this->renameIfPresent($schema->getTable('reset_password_request'), 'hashedToken', 'hashed_token');
        $this->renameIfPresent($schema->getTable('reset_password_request'), 'requestedAt', 'requested_at');
        $this->renameIfPresent($schema->getTable('reset_password_request'), 'expiresAt', 'expires_at');

        $this->renameIfPresent($schema->getTable('user_invitations'), 'expiresAt', 'expires_at');
        $this->renameIfPresent($schema->getTable('user_invitations'), 'reminderSentAt', 'reminder_sent_at');
    }

    /**
     * A fresh install's schema is built straight from the ORM mapping (src/InstallBundle/
     * Installer/Database/Migration.php:90) and already has the target column name, so this
     * guard is what keeps that lineage a clean no-op. Only an upgraded install, which still
     * carries the pre-rename column, has anything for `renameColumn` to do.
     */
    private function renameIfPresent(Table $table, string $from, string $to): void
    {
        if ($table->hasColumn($from)) {
            $table->renameColumn($from, $to);
        }
    }
}
