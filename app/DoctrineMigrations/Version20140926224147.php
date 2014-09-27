<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140926224147 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('RENAME TABLE quote_items TO quote_lines');
        $this->addSql('RENAME TABLE invoice_items TO invoice_lines');
        $this->addSql('ALTER TABLE quote_lines ADD total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE invoice_lines ADD total NUMERIC(10, 2) NOT NULL');
        $this->addSql('UPDATE invoice_lines SET total = (qty * price)');
        $this->addSql('UPDATE quote_lines SET total = (qty * price)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE invoice_lines DROP total');
        $this->addSql('ALTER TABLE quote_lines DROP total');
        $this->addSql('RENAME TABLE quote_lines TO quote_items');
        $this->addSql('RENAME TABLE invoice_lines TO invoice_items');
    }
}
