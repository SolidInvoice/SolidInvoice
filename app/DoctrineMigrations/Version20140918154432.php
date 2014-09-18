<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140918154432 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE roles');
        $this->addSql('ALTER TABLE contact_details ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE app_config CHANGE `key` setting_key VARCHAR(125) NOT NULL, CHANGE `value` setting_value LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL, ADD deleted DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(25) NOT NULL, role VARCHAR(25) NOT NULL, UNIQUE INDEX UNIQ_B63E2EC75E237E06 (name), UNIQUE INDEX UNIQ_B63E2EC757698A6A (role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE app_config CHANGE setting_key `key` VARCHAR(125) NOT NULL, CHANGE setting_value `value` LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact_details DROP created, DROP updated');
        $this->addSql('ALTER TABLE users DROP created, DROP updated, DROP deleted');
    }
}
