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
class Version20140829210211 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE quote_items ADD tax_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE quote_items ADD CONSTRAINT FK_ECE1642CB2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)");
        $this->addSql("CREATE INDEX IDX_ECE1642CB2A824D8 ON quote_items (tax_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE quote_items DROP FOREIGN KEY FK_ECE1642CB2A824D8");
        $this->addSql("DROP INDEX IDX_ECE1642CB2A824D8 ON quote_items");
        $this->addSql("ALTER TABLE quote_items DROP tax_id");
    }
}
