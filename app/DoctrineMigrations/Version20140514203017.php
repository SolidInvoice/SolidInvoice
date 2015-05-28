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
class Version20140514203017 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details ADD method_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F056019883967 FOREIGN KEY (method_id) REFERENCES payment_methods (id)");
        $this->addSql("CREATE INDEX IDX_6B6F056019883967 ON payment_details (method_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F056019883967");
        $this->addSql("DROP INDEX IDX_6B6F056019883967 ON payment_details");
        $this->addSql("ALTER TABLE payment_details DROP method_id");
    }
}
