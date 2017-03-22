<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use CSBill\ClientBundle\Model\Status as ClientStatus;
use CSBill\InvoiceBundle\Model\Graph as InvoiceGraph;
use CSBill\PaymentBundle\Model\Status as PaymentStatus;
use CSBill\QuoteBundle\Model\Graph as QuoteGraph;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version040 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('ALTER TABLE payment_details CHANGE array details LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\'');

        $this->addSql('ALTER TABLE contact_details ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE app_config CHANGE `key` setting_key VARCHAR(125) NOT NULL, CHANGE `value` setting_value LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL, ADD deleted DATETIME DEFAULT NULL');

        $this->addSql('RENAME TABLE quote_items TO quote_lines');
        $this->addSql('RENAME TABLE invoice_items TO invoice_lines');
        $this->addSql('ALTER TABLE quote_lines ADD total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE invoice_lines ADD total NUMERIC(10, 2) NOT NULL');
        $this->addSql('UPDATE invoice_lines SET total = (qty * price)');
        $this->addSql('UPDATE quote_lines SET total = (qty * price)');

        $this->addSql('UPDATE contact_details SET is_primary = 0 WHERE is_primary IS NULL');
        $this->addSql('ALTER TABLE contact_details CHANGE is_primary detail_type VARCHAR(255) NOT NULL;');
        $this->addSql('UPDATE contact_details SET detail_type = "primary" WHERE detail_type = "1"');
        $this->addSql('UPDATE contact_details SET detail_type = "additional" WHERE detail_type = "0"');

        $this->addSql('CREATE TABLE addresses (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, street1 VARCHAR(255) DEFAULT NULL, street2 VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, state VARCHAR(255) DEFAULT NULL, zip VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, INDEX IDX_6FCA751619EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA751619EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');

        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F956BF700BD');
        $this->addSql('DROP INDEX IDX_6A2F2F956BF700BD ON invoices');
        $this->addSql('ALTER TABLE invoices CHANGE status_id status VARCHAR(25) NOT NULL');

        $this->addSql(
            sprintf(
                "UPDATE invoices SET status = CASE status
                  WHEN 9 THEN '%s'
                  WHEN 10 THEN '%s'
                  WHEN 11 THEN '%s'
                  WHEN 12 THEN '%s'
                  WHEN 13 THEN '%s' END",
                InvoiceGraph::STATUS_DRAFT,
                InvoiceGraph::STATUS_PENDING,
                InvoiceGraph::STATUS_PAID,
                InvoiceGraph::STATUS_OVERDUE,
                InvoiceGraph::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_A1B588C56BF700BD');
        $this->addSql('DROP INDEX IDX_A1B588C56BF700BD ON quotes');
        $this->addSql('ALTER TABLE quotes CHANGE status_id status VARCHAR(25) NOT NULL');

        $this->addSql(
            sprintf(
                "UPDATE quotes SET status = CASE status
                  WHEN 14 THEN '%s'
                  WHEN 15 THEN '%s'
                  WHEN 16 THEN '%s'
                  WHEN 17 THEN '%s'
                  WHEN 18 THEN '%s' END",
                QuoteGraph::STATUS_DRAFT,
                QuoteGraph::STATUS_PENDING,
                QuoteGraph::STATUS_ACCEPTED,
                QuoteGraph::STATUS_DECLINED,
                QuoteGraph::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE payment_methods CHANGE settings settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE public public TINYINT(1) DEFAULT NULL, CHANGE enabled enabled TINYINT(1) DEFAULT NULL');

        $this->addSql('DROP TABLE roles');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32BB1A0722');
        $this->addSql('DROP TABLE payment_details');
        $this->addSql('DROP INDEX UNIQ_65D29B32BB1A0722 ON payments');
        $this->addSql('ALTER TABLE payments ADD details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', DROP details_id');

        $this->addSql('ALTER TABLE payment_methods DROP FOREIGN KEY FK_4FABF983C182F730');
        $this->addSql('DROP INDEX IDX_4FABF983C182F730 ON payment_methods');
        $this->addSql('ALTER TABLE payment_methods DROP defaultStatus_id, CHANGE public internal TINYINT(1) DEFAULT NULL');

        $this->addSql('
            INSERT INTO payment_methods
                (id, name, settings, created, updated, deleted, internal, enabled, payment_method)
            VALUES
                (NULL, "Cash", "a:0:{}", NOW(), NOW(), NULL, 1, 1, "cash"),
                (NULL, "Bank Transfer", "a:0:{}", NOW(), NOW(), NULL, 1, 1, "bank_transfer")
        ');

        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B326BF700BD');
        $this->addSql('DROP INDEX IDX_65D29B326BF700BD ON payments');
        $this->addSql('ALTER TABLE payments CHANGE status_id status VARCHAR(25) NOT NULL');

        $this->addSql(
            sprintf(
                "UPDATE payments SET status = CASE status
                  WHEN 3 THEN '%s'
                  WHEN 4 THEN '%s'
                  WHEN 5 THEN '%s'
                  WHEN 6 THEN '%s'
                  WHEN 7 THEN '%s'
                  WHEN 8 THEN '%s'
                  WHEN 9 THEN '%s' END",
                PaymentStatus::STATUS_UNKNOWN,
                PaymentStatus::STATUS_FAILED,
                PaymentStatus::STATUS_SUSPENDED,
                PaymentStatus::STATUS_EXPIRED,
                PaymentStatus::STATUS_CAPTURED,
                PaymentStatus::STATUS_PENDING,
                PaymentStatus::STATUS_CANCELLED
            )
        );

        $this->addSql('CREATE TABLE client_credit (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, value DOUBLE PRECISION NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_4967254D19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_credit ADD CONSTRAINT FK_4967254D19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FABF9837B61A1F6 ON payment_methods (payment_method)');

        $this->addSql('INSERT INTO payment_methods (id, name, settings, created, updated, deleted, internal, enabled, payment_method) VALUES (NULL, "Credit", "a:0:{}", NOW(), NOW(), NULL, 1, 1, "credit")');
        $this->addSql('ALTER TABLE invoices ADD balance DOUBLE PRECISION NOT NULL');

        $this->addSql('INSERT INTO client_credit (client_id, value, created, updated, deleted) SELECT id, 0, NOW(), NOW(), null from clients');

        $this->addSql('ALTER TABLE clients ADD archived TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE invoices ADD archived TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE quotes ADD archived TINYINT(1) DEFAULT NULL');

        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E746BF700BD');
        $this->addSql('DROP TABLE status');
        $this->addSql('DROP INDEX IDX_C82E746BF700BD ON clients');
        $this->addSql('ALTER TABLE clients CHANGE status_id status VARCHAR(25) NOT NULL');

        $this->addSql(
            sprintf(
                "UPDATE clients SET status = CASE status
                  WHEN 1 THEN '%s'
                  WHEN 2 THEN '%s'END",
                ClientStatus::STATUS_ACTIVE,
                ClientStatus::STATUS_INACTIVE
            )
        );

        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, notification_event VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, hipchat VARCHAR(255) NOT NULL, sms VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6000B0D3FD1AEF5E ON notifications (notification_event)');

        $this->addSql('INSERT INTO notifications VALUES (NULL, "client_create", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "invoice_status_update", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "quote_status_update", 1, 0, 0)');
        $this->addSql('INSERT INTO notifications VALUES (NULL, "payment_made", 1, 0, 0)');

        $this->addSql("INSERT INTO `config_sections` VALUES (NULL, NULL, 'hipchat')");
        $this->addSql("INSERT INTO `app_config` VALUES
          (NULL, 'auth_token', '', NULL, LAST_INSERT_ID(), NULL, 'a:0:{}'),
          (NULL, 'room_id', NULL, NULL, LAST_INSERT_ID(), NULL, 'a:0:{}'),
          (NULL, 'server_url', 'https://api.hipchat.com', NULL, LAST_INSERT_ID(), NULL, 'a:0:{}'),
          (NULL, 'notify', NULL, NULL, LAST_INSERT_ID(), 'checkbox', 'a:0:{}'),
          (NULL, 'message_color', 'yellow', NULL, LAST_INSERT_ID(), 'select2', 'a:6:{s:6:\"yellow\";s:6:\"yellow\";s:3:\"red\";s:3:\"red\";s:4:\"gray\";s:4:\"gray\";s:5:\"green\";s:5:\"green\";s:6:\"purple\";s:6:\"purple\";s:6:\"random\";s:6:\"random\";}')
          ");

        $this->addSql("INSERT INTO `config_sections` VALUES (NULL, NULL, 'sms')");
        $this->addSql("INSERT INTO `config_sections` VALUES (NULL, LAST_INSERT_ID(), 'twilio')");
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

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('ALTER TABLE payment_details CHANGE details array LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\'');

        $this->addSql('ALTER TABLE app_config CHANGE setting_key `key` VARCHAR(125) NOT NULL, CHANGE setting_value `value` LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact_details DROP created, DROP updated');
        $this->addSql('ALTER TABLE users DROP created, DROP updated, DROP deleted');

        $this->addSql('ALTER TABLE invoice_lines DROP total');
        $this->addSql('ALTER TABLE quote_lines DROP total');
        $this->addSql('RENAME TABLE quote_lines TO quote_items');
        $this->addSql('RENAME TABLE invoice_lines TO invoice_items');

        $this->addSql('UPDATE contact_details SET detail_type = 1 WHERE detail_type = "primary"');
        $this->addSql('UPDATE contact_details SET detail_type = 0 WHERE detail_type = "additional"');
        $this->addSql('ALTER TABLE contact_details CHANGE detail_type is_primary tinyint(1) DEFAULT NULL;');
        $this->addSql('UPDATE contact_details SET is_primary = NULL WHERE is_primary = 0');

        $this->addSql('DROP TABLE addresses');

        $this->addSql(
            sprintf(
                "UPDATE invoices SET status = CASE status
                  WHEN '%s' THEN 9
                  WHEN '%s' THEN 10
                  WHEN '%s' THEN 11
                  WHEN '%s' THEN 12
                  WHEN '%s' THEN 13 END",
                InvoiceGraph::STATUS_DRAFT,
                InvoiceGraph::STATUS_PENDING,
                InvoiceGraph::STATUS_PAID,
                InvoiceGraph::STATUS_OVERDUE,
                InvoiceGraph::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE invoices CHANGE status status_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F956BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_6A2F2F956BF700BD ON invoices (status_id)');

        $this->addSql(
            sprintf(
                "UPDATE quotes SET status = CASE status
                  WHEN '%s' THEN 14
                  WHEN '%s' THEN 15
                  WHEN '%s' THEN 16
                  WHEN '%s' THEN 17
                  WHEN '%s' THEN 18 END",
                QuoteGraph::STATUS_DRAFT,
                QuoteGraph::STATUS_PENDING,
                QuoteGraph::STATUS_ACCEPTED,
                QuoteGraph::STATUS_DECLINED,
                QuoteGraph::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE quotes CHANGE status status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C56BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_A1B588C56BF700BD ON quotes (status_id)');

        $this->addSql('ALTER TABLE payment_methods CHANGE settings settings LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE public public TINYINT(1) NOT NULL, CHANGE enabled enabled TINYINT(1) NOT NULL');

        $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(25) NOT NULL, role VARCHAR(25) NOT NULL, UNIQUE INDEX UNIQ_B63E2EC75E237E06 (name), UNIQUE INDEX UNIQ_B63E2EC757698A6A (role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_details (id INT AUTO_INCREMENT NOT NULL, details LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payments ADD details_id INT DEFAULT NULL, DROP details');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32BB1A0722 FOREIGN KEY (details_id) REFERENCES payment_details (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B32BB1A0722 ON payments (details_id)');

        $this->addSql('ALTER TABLE payment_methods ADD defaultStatus_id INT DEFAULT NULL, CHANGE internal public TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_methods ADD CONSTRAINT FK_4FABF983C182F730 FOREIGN KEY (defaultStatus_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_4FABF983C182F730 ON payment_methods (defaultStatus_id)');

        $this->addSql(
            sprintf(
                "UPDATE payments SET status = CASE status
                  WHEN '%s' THEN 3
                  WHEN '%s' THEN 4
                  WHEN '%s' THEN 5
                  WHEN '%s' THEN 6
                  WHEN '%s' THEN 7
                  WHEN '%s' THEN 8
                  WHEN '%s' THEN 9 END",
                PaymentStatus::STATUS_UNKNOWN,
                PaymentStatus::STATUS_FAILED,
                PaymentStatus::STATUS_SUSPENDED,
                PaymentStatus::STATUS_EXPIRED,
                PaymentStatus::STATUS_CAPTURED,
                PaymentStatus::STATUS_PENDING,
                PaymentStatus::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE payments CHANGE status status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B326BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_65D29B326BF700BD ON payments (status_id)');

        $this->addSql('DROP TABLE client_credit');

        $this->addSql('ALTER TABLE clients DROP archived');
        $this->addSql('ALTER TABLE invoices DROP archived');
        $this->addSql('ALTER TABLE quotes DROP archived');

        $this->addSql(
            sprintf(
                "UPDATE clients SET status = CASE status
                  WHEN '%s' THEN 1
                  WHEN '%s' THEN 2 END",
                ClientStatus::STATUS_ACTIVE,
                ClientStatus::STATUS_INACTIVE
            )
        );

        $this->addSql('CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(125) NOT NULL, `label` VARCHAR(125) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, entity VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clients CHANGE status status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E746BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_C82E746BF700BD ON clients (status_id)');

        $this->addSql('DELETE FROM app_config WHERE section_id = (SELECT id FROM config_sections WHERE name = "sms")');
        $this->addSql('DELETE FROM app_config WHERE section_id = (SELECT id FROM config_sections WHERE name = "hipchat")');
        $this->addSql('DELETE FROM app_config WHERE section_id = (SELECT id FROM config_sections WHERE name = "twilio")');
        $this->addSql('DELETE FROM config_sections WHERE name = "sms"');
        $this->addSql('DELETE FROM config_sections WHERE name = "hipchat"');
        $this->addSql('DELETE FROM config_sections WHERE name = "twilio"');
        $this->addSql('DROP TABLE notifications');
    }
}
