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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version050 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE client_credit SET `value` = `value` * 100');
        $this->addSql('ALTER TABLE client_credit CHANGE `value` `value` INT NOT NULL');

        $this->addSql('UPDATE quote_lines SET price = price * 100, total = total * 100');
        $this->addSql('ALTER TABLE quote_lines CHANGE price price INT NOT NULL, CHANGE total total INT NOT NULL');

        $this->addSql('UPDATE quotes SET total = total * 100, base_total = base_total * 100, tax = tax * 100');
        $this->addSql('ALTER TABLE quotes CHANGE total total INT NOT NULL, CHANGE base_total base_total INT NOT NULL, CHANGE tax tax INT DEFAULT NULL');

        $this->addSql('UPDATE invoices SET total = total * 100, base_total = base_total * 100, tax = tax * 100, balance = balance * 100');
        $this->addSql('ALTER TABLE invoices CHANGE total total INT NOT NULL, CHANGE base_total base_total INT NOT NULL, CHANGE tax tax INT DEFAULT NULL, CHANGE balance balance INT NOT NULL');

        $this->addSql('UPDATE invoice_lines SET total = total * 100');
        $this->addSql('ALTER TABLE invoice_lines CHANGE price price INT NOT NULL, CHANGE total total INT NOT NULL');

        $this->addSql('UPDATE payments SET amount = amount * 100');
        $this->addSql('ALTER TABLE payments CHANGE amount amount INT NOT NULL');

        $this->addSql('CREATE TABLE api_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(125) NOT NULL, token VARCHAR(125) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_2CAD560EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_tokens ADD CONSTRAINT FK_2CAD560EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql('CREATE TABLE api_token_history (id INT AUTO_INCREMENT NOT NULL, token_id INT DEFAULT NULL, ip VARCHAR(255) NOT NULL, resource VARCHAR(125) NOT NULL, method VARCHAR(25) NOT NULL, requestData LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', userAgent VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_61D8DC4441DEE7B9 (token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_token_history ADD CONSTRAINT FK_61D8DC4441DEE7B9 FOREIGN KEY (token_id) REFERENCES api_tokens (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE client_credit CHANGE value value DOUBLE PRECISION NOT NULL');
        $this->addSql('UPDATE client_credit SET `value` = `value` / 100');

        $this->addSql('ALTER TABLE invoice_lines CHANGE price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('UPDATE invoice_lines SET price = price / 100');

        $this->addSql('ALTER TABLE invoices CHANGE total total DOUBLE PRECISION NOT NULL, CHANGE base_total base_total DOUBLE PRECISION NOT NULL, CHANGE balance balance DOUBLE PRECISION NOT NULL, CHANGE tax tax DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('UPDATE invoices SET total = total / 100, base_total = base_total / 100, tax = tax / 100, balance = balance / 100');

        $this->addSql('ALTER TABLE payments CHANGE amount amount DOUBLE PRECISION NOT NULL');
        $this->addSql('UPDATE payments SET amount = amount / 100');

        $this->addSql('ALTER TABLE quote_lines CHANGE price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('UPDATE quote_lines SET price = price / 100');

        $this->addSql('ALTER TABLE quotes CHANGE total total DOUBLE PRECISION NOT NULL, CHANGE base_total base_total DOUBLE PRECISION NOT NULL, CHANGE tax tax DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('UPDATE quotes SET total = total / 100, base_total = base_total / 100, tax = tax / 100');

        $this->addSql('DROP TABLE api_token_history');
        $this->addSql('DROP TABLE api_tokens');
    }
}
