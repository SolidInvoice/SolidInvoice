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

final class Version30100_3 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add visibility column to custom_field for invoice and quote targets.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('custom_field');
        if (! $table->hasColumn('visibility')) {
            $table->addColumn('visibility', 'string', ['length' => 32, 'notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('custom_field');
        if ($table->hasColumn('visibility')) {
            $table->dropColumn('visibility');
        }
    }
}
