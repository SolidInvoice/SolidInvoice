<?php

namespace Application\Migrations;

use CSBill\InvoiceBundle\Model\Graph;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150124112808 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

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
                Graph::STATUS_DRAFT,
                Graph::STATUS_PENDING,
                Graph::STATUS_PAID,
                Graph::STATUS_OVERDUE,
                Graph::STATUS_CANCELLED
            )
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            sprintf(
                "UPDATE invoices SET status = CASE status
                  WHEN '%s' THEN 9
                  WHEN '%s' THEN 10
                  WHEN '%s' THEN 11
                  WHEN '%s' THEN 12
                  WHEN '%s' THEN 13 END",
                Graph::STATUS_DRAFT,
                Graph::STATUS_PENDING,
                Graph::STATUS_PAID,
                Graph::STATUS_OVERDUE,
                Graph::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE invoices CHANGE status status_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F956BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_6A2F2F956BF700BD ON invoices (status_id)');
    }
}
