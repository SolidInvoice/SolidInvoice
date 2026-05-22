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
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30000_11 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create billing_templates table for user-managed invoice/quote HTML, PDF and email templates';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(BillingTemplate::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(BillingTemplate::TABLE_NAME);
        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('type', Types::STRING, ['length' => 16, 'notnull' => true]);
        $table->addColumn('variant', Types::STRING, ['length' => 16, 'notnull' => true]);
        $table->addColumn('name', Types::STRING, ['length' => 100, 'notnull' => true]);
        $table->addColumn('content', Types::TEXT, ['notnull' => true]);
        $table->addColumn('active', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
        $table->addColumn('system', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
        $table->addColumn('created', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $table->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['company_id', 'type', 'variant'], 'billing_templates_lookup_idx');

        $table->addForeignKeyConstraint(
            'companies',
            ['company_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
        );
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(BillingTemplate::TABLE_NAME)) {
            $schema->dropTable(BillingTemplate::TABLE_NAME);
        }
    }
}
