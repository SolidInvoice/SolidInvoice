<?php

namespace Application\Migrations;

use CSBill\QuoteBundle\Model\Graph;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150126142600 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

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
                Graph::STATUS_DRAFT,
                Graph::STATUS_PENDING,
                Graph::STATUS_ACCEPTED,
                Graph::STATUS_DECLINED,
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
                "UPDATE quotes SET status = CASE status
                  WHEN '%s' THEN 14
                  WHEN '%s' THEN 15
                  WHEN '%s' THEN 16
                  WHEN '%s' THEN 17
                  WHEN '%s' THEN 18 END",
                Graph::STATUS_DRAFT,
                Graph::STATUS_PENDING,
                Graph::STATUS_ACCEPTED,
                Graph::STATUS_DECLINED,
                Graph::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE quotes CHANGE status status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C56BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_A1B588C56BF700BD ON quotes (status_id)');
    }
}
