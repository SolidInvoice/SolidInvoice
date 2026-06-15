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

final class Version30000_12 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add facebook_id column to users table for Facebook OAuth login';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('users');
        $table->addColumn('facebook_id', Types::STRING, ['length' => 45, 'notnull' => false]);
        $table->addIndex(['facebook_id'], 'IDX_USERS_FACEBOOK_ID');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');
        $table->dropIndex('IDX_USERS_FACEBOOK_ID');
        $table->dropColumn('facebook_id');
    }
}
