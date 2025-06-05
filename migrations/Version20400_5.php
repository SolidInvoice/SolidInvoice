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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20400_5 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $usersTable = $schema->getTable('users');
        $usersTable->addColumn('totp_secret', Types::STRING, [
            'notnull' => false,
            'length' => 45,
        ]);
        $usersTable->addColumn('auth_code', Types::STRING, [
            'notnull' => false,
            'length' => 45,
        ]);
        $usersTable->addColumn('email_auth_enabled', Types::BOOLEAN, [
            'notnull' => false,
        ]);
        $usersTable->addColumn('trusted_version', Types::INTEGER, [
            'notnull' => false,
            'default' => 0,
        ]);
        $usersTable->addColumn('backup_codes', Types::JSON, [
            'notnull' => false,
        ]);
    }

    public function down(Schema $schema): void
    {
        $usersTable = $schema->getTable('users');
        $usersTable->dropColumn('first_name');
        $usersTable->dropColumn('last_name');
    }
}
