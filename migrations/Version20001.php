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

final class Version20001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->update('app_config', ['setting_value' => 'skin-solidinvoice-default'], ['setting_value' => 'skin-solidinoice-default']);
    }

    public function down(Schema $schema): void
    {
    }
}
