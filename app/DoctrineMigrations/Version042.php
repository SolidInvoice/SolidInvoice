<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version042 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('ALTER TABLE security_token CHANGE payment_name gateway_name VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE app_config set setting_value = "info@csbill.org" WHERE setting_key  = "from_address"');
        $this->addSql('UPDATE app_config set setting_value = "CSBill" WHERE setting_key  = "from_name"');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('ALTER TABLE security_token CHANGE gateway_name payment_name VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE app_config set setting_value = "" WHERE setting_key  = "from_address"');
        $this->addSql('UPDATE app_config set setting_value = "" WHERE setting_key  = "from_name"');
    }
}
