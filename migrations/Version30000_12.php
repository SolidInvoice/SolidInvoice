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
use Doctrine\Migrations\AbstractMigration;

/**
 * Increases the totp_secret column length from 45 to 64.
 *
 * scheb/2fa-totp v7+ generates TOTP secrets via Base32(random_bytes(32)),
 * which produces 52-character strings — exceeding the old 45-character limit.
 */
final class Version30000_12 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $usersTable = $schema->getTable('users');
        $usersTable->modifyColumn('totp_secret', [
            'length' => 64,
            'notnull' => false,
        ]);
    }

    public function down(Schema $schema): void
    {
        $usersTable = $schema->getTable('users');
        $usersTable->modifyColumn('totp_secret', [
            'length' => 45,
            'notnull' => false,
        ]);
    }
}
