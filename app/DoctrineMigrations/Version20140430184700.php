<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140430184700 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details ADD invoice_id INT DEFAULT NULL, ADD status_id INT DEFAULT NULL, ADD enabled TINYINT(1) NOT NULL, ADD public TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05602989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05606BF700BD FOREIGN KEY (status_id) REFERENCES status (id)");
        $this->addSql("CREATE INDEX IDX_6B6F05602989F1FD ON payment_details (invoice_id)");
        $this->addSql("CREATE INDEX IDX_6B6F05606BF700BD ON payment_details (status_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05602989F1FD");
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05606BF700BD");
        $this->addSql("DROP INDEX IDX_6B6F05602989F1FD ON payment_details");
        $this->addSql("DROP INDEX IDX_6B6F05606BF700BD ON payment_details");
        $this->addSql("ALTER TABLE payment_details DROP invoice_id, DROP status_id, DROP enabled, DROP public");
    }
}
