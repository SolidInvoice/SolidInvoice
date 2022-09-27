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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;

final class Version20102 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function isTransactional(): bool
    {
        return \PHP_VERSION_ID < 80000;
    }

    /**
     * @var Schema
     */
    private $schema;

    public function up(Schema $schema): void
    {
        /** @var SystemConfig $config */
        $config = $this->container->get('settings');

        $config->remove('design/system/theme');
    }

    public function down(Schema $schema): void
    {
    }
}