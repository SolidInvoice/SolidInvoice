<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141016212113 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE contact_details SET is_primary = 0 WHERE is_primary IS NULL');
        $this->addSql('ALTER TABLE contact_details CHANGE is_primary detail_type VARCHAR(255) NOT NULL;');
        $this->addSql('UPDATE contact_details SET detail_type = "primary" WHERE detail_type = "1"');
        $this->addSql('UPDATE contact_details SET detail_type = "additional" WHERE detail_type = "0"');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('UPDATE contact_details SET detail_type = 1 WHERE detail_type = "primary"');
        $this->addSql('UPDATE contact_details SET detail_type = 0 WHERE detail_type = "additional"');
        $this->addSql('ALTER TABLE contact_details CHANGE detail_type is_primary tinyint(1) DEFAULT NULL;');
        $this->addSql('UPDATE contact_details SET is_primary = NULL WHERE is_primary = 0');
    }
}
