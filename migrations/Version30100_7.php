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

final class Version30100_7 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users.oidc_id to store the OpenID Connect subject identifier';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('users');

        if (! $table->hasColumn('oidc_id')) {
            $table->addColumn('oidc_id', Types::STRING, [
                'length' => 255,
                'notnull' => false,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');

        if ($table->hasColumn('oidc_id')) {
            $table->dropColumn('oidc_id');
        }
    }
}
