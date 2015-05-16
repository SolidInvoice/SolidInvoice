<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150512205111 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, notification_event VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, hipchat VARCHAR(255) NOT NULL, sms VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6000B0D3FD1AEF5E ON notifications (notification_event)');

        $this->addSql('INSERT INTO notifications VALUES (NULL, "client_create", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "invoice_create", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "invoice_status_update", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "quote_create", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "quote_status_update", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "payment_made", 1, 0, 0)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM app_config WHERE section_id = 6');
        $this->addSql('DELETE FROM config_sections WHERE id = 6');
    }
}
