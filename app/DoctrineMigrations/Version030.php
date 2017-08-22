<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version030 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE quotes ADD terms LONGTEXT DEFAULT NULL, ADD notes LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE invoices ADD terms LONGTEXT DEFAULT NULL, ADD notes LONGTEXT DEFAULT NULL");

        $this->addSql("CREATE TABLE tax_rates (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, rate VARCHAR(32) NOT NULL, tax_type VARCHAR(32) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE invoice_items ADD tax_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE invoice_items ADD CONSTRAINT FK_DCC4B9F8B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)");
        $this->addSql("CREATE INDEX IDX_DCC4B9F8B2A824D8 ON invoice_items (tax_id)");

        $this->addSql("ALTER TABLE tax_rates CHANGE rate rate DOUBLE PRECISION NOT NULL");

        $this->addSql("ALTER TABLE quote_items ADD tax_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE quote_items ADD CONSTRAINT FK_ECE1642CB2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)");
        $this->addSql("CREATE INDEX IDX_ECE1642CB2A824D8 ON quote_items (tax_id)");

        $this->addSql("ALTER TABLE quotes ADD tax DOUBLE PRECISION DEFAULT NULL");

        $this->addSql("ALTER TABLE invoices ADD tax DOUBLE PRECISION DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE invoices DROP terms, DROP notes");
        $this->addSql("ALTER TABLE quotes DROP terms, DROP notes");

        $this->addSql("ALTER TABLE invoice_items DROP FOREIGN KEY FK_DCC4B9F8B2A824D8");
        $this->addSql("DROP TABLE tax_rates");
        $this->addSql("DROP INDEX IDX_DCC4B9F8B2A824D8 ON invoice_items");
        $this->addSql("ALTER TABLE invoice_items DROP tax_id");

        $this->addSql("ALTER TABLE tax_rates CHANGE rate rate VARCHAR(32) NOT NULL");

        $this->addSql("ALTER TABLE quote_items DROP FOREIGN KEY FK_ECE1642CB2A824D8");
        $this->addSql("DROP INDEX IDX_ECE1642CB2A824D8 ON quote_items");
        $this->addSql("ALTER TABLE quote_items DROP tax_id");

        $this->addSql("ALTER TABLE quotes DROP tax");

        $this->addSql("ALTER TABLE invoices DROP tax");
    }
}
