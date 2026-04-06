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

final class Version30000_6 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mark all offline payment methods as internal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE payment_methods SET internal = 1 WHERE factory = 'offline'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE payment_methods SET internal = 0 WHERE factory = 'offline'");
    }
}
