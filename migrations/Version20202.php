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

final class Version20202 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $contactTypes = $schema->getTable('contact_types');

        foreach ($contactTypes->getIndexes() as $index) {
            if ($index->isUnique() && $index->getColumns() === ['name']) {
                $contactTypes->dropIndex($index->getName());
            }
        }

        $contactTypes->addUniqueIndex(['name', 'company_id']);
    }
}
