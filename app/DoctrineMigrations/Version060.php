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

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version060 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE recurring_invoices (id INT AUTO_INCREMENT NOT NULL, invoice_id INT DEFAULT NULL, frequency VARCHAR(255) DEFAULT NULL, date_start DATE NOT NULL, date_end DATE DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, archived TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_FE93E2842989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recurring_invoices ADD CONSTRAINT FK_FE93E2842989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)');
        $this->addSql('ALTER TABLE invoices ADD is_recurring TINYINT(1) NOT NULL');

        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B3219EB6921');
        $this->addSql('DROP INDEX IDX_65D29B3219EB6921 ON payments');
        $this->addSql('ALTER TABLE payments CHANGE client_id client INT DEFAULT NULL, ADD number VARCHAR(255) DEFAULT NULL, ADD description VARCHAR(255) DEFAULT NULL, ADD client_email VARCHAR(255) DEFAULT NULL, CHANGE amount total_amount INT DEFAULT NULL, CHANGE currency currency_code VARCHAR(255) DEFAULT NULL, ADD client_id VARCHAR(255) DEFAULT NULL, CHANGE details details LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32C7440455 FOREIGN KEY (client) REFERENCES clients (id)');
        $this->addSql('CREATE INDEX IDX_65D29B32C7440455 ON payments (client)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE recurring_invoices');
        $this->addSql('ALTER TABLE invoices DROP is_recurring');

        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32C7440455');
        $this->addSql('DROP INDEX IDX_65D29B32C7440455 ON payments');
        $this->addSql('ALTER TABLE payments ADD amount INT NOT NULL, ADD currency VARCHAR(24) NOT NULL, DROP client, DROP number, DROP description, DROP client_email, DROP total_amount, DROP currency_code, CHANGE client_id client_id INT DEFAULT NULL, CHANGE details details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B3219EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');
        $this->addSql('CREATE INDEX IDX_65D29B3219EB6921 ON payments (client_id)');
    }
}
