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
        $this->addSql('INSERT INTO notifications VALUES (NULL, "invoice_status_update", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "quote_status_update", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "payment_made", 1, 0, 0)');

        $this->addSql("INSERT INTO `config_sections` VALUES (NULL, NULL, 'hipchats')");
        $this->addSql("INSERT INTO `app_config` VALUES
          (NULL, 'auth_token', '', NULL, LAST_INSERT_ID(), NULL, 'a:0:{}'),
          (NULL, 'room_id', NULL, NULL, LAST_INSERT_ID(), NULL, 'a:0:{}'),
          (NULL, 'server_url', 'https://api.hipchat.com', NULL, LAST_INSERT_ID(), NULL, 'a:0:{}'),
          (NULL, 'notify', NULL, NULL, LAST_INSERT_ID(), 'checkbox', 'a:0:{}'),
          (NULL, 'message_color', 'yellow', NULL, LAST_INSERT_ID(), 'select2', 'a:6:{s:6:\"yellow\";s:6:\"yellow\";s:3:\"red\";s:3:\"red\";s:4:\"gray\";s:4:\"gray\";s:5:\"green\";s:5:\"green\";s:6:\"purple\";s:6:\"purple\";s:6:\"random\";s:6:\"random\";}')
          ");

        $this->addSql("INSERT INTO `config_sections` VALUES (NULL, NULL, 'smss')");
        $this->addSql("INSERT INTO `config_sections` VALUES (NULL, LAST_INSERT_ID(), 'twilios')");
        $this->addSql("INSERT INTO `app_config` VALUES
          (NULL, 'number', NULL, NULL, LAST_INSERT_ID(), 'text', 'a:0:{}'),
          (NULL, 'sid', NULL, NULL, LAST_INSERT_ID(), 'text', 'a:0:{}'),
          (NULL, 'token', NULL, NULL, LAST_INSERT_ID(), 'text', 'a:0:{}')
        ");

        $this->addSql('
            INSERT INTO
              app_config
                (
                  id,
                  setting_key,
                  setting_value,
                  description,
                  section_id,
                  field_type,
                  field_options
                )
            VALUES
              (
                NULL,
                "bcc_address",
                NULL,
                "Send BCC copy of invoice to this address",
                (select id from config_sections where name = "invoice"),
                "email",
                "a:0:{}"
              )
        ');

        $this->addSql('
            INSERT INTO
              app_config
                (
                  id,
                  setting_key,
                  setting_value,
                  description,
                  section_id,
                  field_type,
                  field_options
                )
            VALUES
              (
                NULL,
                "bcc_address",
                NULL,
                "Send BCC copy of quote to this address",
                (select id from config_sections where name = "quote"),
                "email",
                "a:0:{}"
              )
        ');

        $this->addSql('ALTER TABLE users ADD mobile VARCHAR(255) DEFAULT NULL');
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
