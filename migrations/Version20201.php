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

final class Version20201 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('quote_contact')
            ->dropPrimaryKey();

        $schema->getTable('invoice_contact')
            ->dropPrimaryKey();

        $schema->getTable('recurringinvoice_contact')
            ->dropPrimaryKey();
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('quote_contact')
            ->setPrimaryKey(['quote_id', 'contact_id']);

        $schema->getTable('invoice_contact')
            ->setPrimaryKey(['invoice_id', 'contact_id']);

        $schema->getTable('recurringinvoice_contact')
            ->setPrimaryKey(['recurringinvoice_id', 'contact_id']);
    }
}
