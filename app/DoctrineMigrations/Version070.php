<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version070 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
	// this up() migration is auto-generated, please modify it to your needs
	$this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

	//$this->addSql('ALTER TABLE clients ADD currency VARCHAR(6) DEFAULT NULL');

	$this->addSql('ALTER TABLE client_credit ADD value_amount INT DEFAULT NULL, ADD value_currency VARCHAR(3) DEFAULT NULL, DROP value');
	$this->addSql('ALTER TABLE quotes ADD total_currency VARCHAR(3) DEFAULT NULL, ADD baseTotal_amount INT DEFAULT NULL, ADD baseTotal_currency VARCHAR(3) DEFAULT NULL, ADD tax_amount INT DEFAULT NULL, ADD tax_currency VARCHAR(3) DEFAULT NULL, DROP total, DROP base_total, CHANGE tax total_amount INT DEFAULT NULL');
	$this->addSql('ALTER TABLE quote_lines DROP FOREIGN KEY FK_ECE1642CB2A824D8');
	$this->addSql('ALTER TABLE quote_lines DROP FOREIGN KEY FK_ECE1642CDB805178');
	$this->addSql('ALTER TABLE quote_lines ADD price_amount INT DEFAULT NULL, ADD price_currency VARCHAR(3) DEFAULT NULL, ADD total_amount INT DEFAULT NULL, ADD total_currency VARCHAR(3) DEFAULT NULL, DROP price, DROP total');
	$this->addSql('DROP INDEX idx_ece1642cdb805178 ON quote_lines');
	$this->addSql('CREATE INDEX IDX_42FE01F7DB805178 ON quote_lines (quote_id)');
	$this->addSql('DROP INDEX idx_ece1642cb2a824d8 ON quote_lines');
	$this->addSql('CREATE INDEX IDX_42FE01F7B2A824D8 ON quote_lines (tax_id)');
	$this->addSql('ALTER TABLE quote_lines ADD CONSTRAINT FK_ECE1642CB2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)');
	$this->addSql('ALTER TABLE quote_lines ADD CONSTRAINT FK_ECE1642CDB805178 FOREIGN KEY (quote_id) REFERENCES quotes (id)');
	$this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_DCC4B9F82989F1FD');
	$this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_DCC4B9F8B2A824D8');
	$this->addSql('ALTER TABLE invoice_lines ADD price_amount INT DEFAULT NULL, ADD price_currency VARCHAR(3) DEFAULT NULL, ADD total_amount INT DEFAULT NULL, ADD total_currency VARCHAR(3) DEFAULT NULL, DROP price, DROP total');
	$this->addSql('DROP INDEX idx_dcc4b9f82989f1fd ON invoice_lines');
	$this->addSql('CREATE INDEX IDX_72DBDC232989F1FD ON invoice_lines (invoice_id)');
	$this->addSql('DROP INDEX idx_dcc4b9f8b2a824d8 ON invoice_lines');
	$this->addSql('CREATE INDEX IDX_72DBDC23B2A824D8 ON invoice_lines (tax_id)');
	$this->addSql('ALTER TABLE invoice_lines ADD CONSTRAINT FK_DCC4B9F82989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)');
	$this->addSql('ALTER TABLE invoice_lines ADD CONSTRAINT FK_DCC4B9F8B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)');
	$this->addSql('ALTER TABLE invoices ADD total_currency VARCHAR(3) DEFAULT NULL, ADD baseTotal_amount INT DEFAULT NULL, ADD baseTotal_currency VARCHAR(3) DEFAULT NULL, ADD balance_amount INT DEFAULT NULL, ADD balance_currency VARCHAR(3) DEFAULT NULL, ADD tax_amount INT DEFAULT NULL, ADD tax_currency VARCHAR(3) DEFAULT NULL, DROP total, DROP base_total, DROP balance, CHANGE tax total_amount INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
	// this down() migration is auto-generated, please modify it to your needs
	$this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

	$this->addSql('ALTER TABLE clients DROP currency');

	$this->addSql('ALTER TABLE client_credit ADD value INT NOT NULL, DROP value_amount, DROP value_currency');
	$this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_72DBDC232989F1FD');
	$this->addSql('ALTER TABLE invoice_lines DROP FOREIGN KEY FK_72DBDC23B2A824D8');
	$this->addSql('ALTER TABLE invoice_lines ADD price INT NOT NULL, ADD total INT NOT NULL, DROP price_amount, DROP price_currency, DROP total_amount, DROP total_currency');
	$this->addSql('DROP INDEX idx_72dbdc232989f1fd ON invoice_lines');
	$this->addSql('CREATE INDEX IDX_DCC4B9F82989F1FD ON invoice_lines (invoice_id)');
	$this->addSql('DROP INDEX idx_72dbdc23b2a824d8 ON invoice_lines');
	$this->addSql('CREATE INDEX IDX_DCC4B9F8B2A824D8 ON invoice_lines (tax_id)');
	$this->addSql('ALTER TABLE invoice_lines ADD CONSTRAINT FK_72DBDC232989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)');
	$this->addSql('ALTER TABLE invoice_lines ADD CONSTRAINT FK_72DBDC23B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)');
	$this->addSql('ALTER TABLE invoices ADD total INT NOT NULL, ADD base_total INT NOT NULL, ADD tax INT DEFAULT NULL, ADD balance INT NOT NULL, DROP total_amount, DROP total_currency, DROP baseTotal_amount, DROP baseTotal_currency, DROP balance_amount, DROP balance_currency, DROP tax_amount, DROP tax_currency');
	$this->addSql('ALTER TABLE quote_lines DROP FOREIGN KEY FK_42FE01F7DB805178');
	$this->addSql('ALTER TABLE quote_lines DROP FOREIGN KEY FK_42FE01F7B2A824D8');
	$this->addSql('ALTER TABLE quote_lines ADD price INT NOT NULL, ADD total INT NOT NULL, DROP price_amount, DROP price_currency, DROP total_amount, DROP total_currency');
	$this->addSql('DROP INDEX idx_42fe01f7db805178 ON quote_lines');
	$this->addSql('CREATE INDEX IDX_ECE1642CDB805178 ON quote_lines (quote_id)');
	$this->addSql('DROP INDEX idx_42fe01f7b2a824d8 ON quote_lines');
	$this->addSql('CREATE INDEX IDX_ECE1642CB2A824D8 ON quote_lines (tax_id)');
	$this->addSql('ALTER TABLE quote_lines ADD CONSTRAINT FK_42FE01F7DB805178 FOREIGN KEY (quote_id) REFERENCES quotes (id)');
	$this->addSql('ALTER TABLE quote_lines ADD CONSTRAINT FK_42FE01F7B2A824D8 FOREIGN KEY (tax_id) REFERENCES tax_rates (id)');
	$this->addSql('ALTER TABLE quotes ADD total INT NOT NULL, ADD base_total INT NOT NULL, ADD tax INT DEFAULT NULL, DROP total_amount, DROP total_currency, DROP baseTotal_amount, DROP baseTotal_currency, DROP tax_amount, DROP tax_currency');
    }
}
