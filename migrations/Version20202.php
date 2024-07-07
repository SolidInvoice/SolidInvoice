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

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20202 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
        $this->skipIf(! $this->platform instanceof MySQLPlatform, 'Migration can only be executed safely on "mysql".');

        $contactTypes = $schema->getTable('contact_types');

        foreach ($contactTypes->getIndexes() as $index) {
            if ($index->isUnique() && $index->getColumns() === ['name']) {
                $contactTypes->dropIndex($index->getName());
            }
        }

        $contactTypes->addUniqueIndex(['name', 'company_id']);
    }
}
