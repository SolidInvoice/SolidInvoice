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

final class Version30000_7 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable unique custom_domain column to companies for SaaS custom domain support';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('companies');

        if (! $table->hasColumn('custom_domain')) {
            $table->addColumn('custom_domain', 'string', [
                'length' => 253,
                'notnull' => false,
            ]);
        }

        if (! $table->hasIndex('uniq_companies_custom_domain')) {
            $table->addUniqueIndex(['custom_domain'], 'uniq_companies_custom_domain');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('companies');

        if ($table->hasIndex('uniq_companies_custom_domain')) {
            $table->dropIndex('uniq_companies_custom_domain');
        }

        if ($table->hasColumn('custom_domain')) {
            $table->dropColumn('custom_domain');
        }
    }
}
