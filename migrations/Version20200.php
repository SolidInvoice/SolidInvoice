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
use Doctrine\DBAL\Types\JsonType;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20200 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function isTransactional(): bool
    {
        return \PHP_VERSION_ID < 80000;
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('payments')->changeColumn('details', [
            'type' => new JsonType(),
            'notnull' => true,
        ]);
    }

    public function down(Schema $schema): void
    {
    }
}
