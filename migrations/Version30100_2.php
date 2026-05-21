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

final class Version30100_2 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional default_value column to custom_field.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('custom_field');
        if (! $table->hasColumn('default_value')) {
            $table->addColumn('default_value', 'text', ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('custom_field');
        if ($table->hasColumn('default_value')) {
            $table->dropColumn('default_value');
        }
    }
}
