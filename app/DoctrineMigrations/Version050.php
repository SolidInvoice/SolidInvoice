<?php

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
    }
}
