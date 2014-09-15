<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140825225605 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE tax_rates (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, rate VARCHAR(32) NOT NULL, tax_type VARCHAR(32) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE invoice_items ADD tax_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE invoice_items ADD CONSTRAINT FK_DCC4B9F8B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)");
        $this->addSql("CREATE INDEX IDX_DCC4B9F8B2A824D8 ON invoice_items (tax_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE invoice_items DROP FOREIGN KEY FK_DCC4B9F8B2A824D8");
        $this->addSql("DROP TABLE tax_rates");
        $this->addSql("DROP INDEX IDX_DCC4B9F8B2A824D8 ON invoice_items");
        $this->addSql("ALTER TABLE invoice_items DROP tax_id");
    }
}
