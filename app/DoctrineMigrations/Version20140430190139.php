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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140430190139 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details DROP public");
        $this->addSql("ALTER TABLE payment_methods ADD public TINYINT(1) NOT NULL, ADD defaultStatus_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payment_methods ADD CONSTRAINT FK_4FABF983C182F730 FOREIGN KEY (defaultStatus_id) REFERENCES status (id)");
        $this->addSql("CREATE INDEX IDX_4FABF983C182F730 ON payment_methods (defaultStatus_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details ADD public TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE payment_methods DROP FOREIGN KEY FK_4FABF983C182F730");
        $this->addSql("DROP INDEX IDX_4FABF983C182F730 ON payment_methods");
        $this->addSql("ALTER TABLE payment_methods DROP public, DROP defaultStatus_id");
    }
}
