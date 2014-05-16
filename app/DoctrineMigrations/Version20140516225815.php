<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140516225815 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, invoice_id INT DEFAULT NULL, method_id INT DEFAULT NULL, status_id INT DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(24) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, INDEX IDX_65D29B322989F1FD (invoice_id), INDEX IDX_65D29B3219883967 (method_id), INDEX IDX_65D29B326BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE payments ADD CONSTRAINT FK_65D29B322989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)");
        $this->addSql("ALTER TABLE payments ADD CONSTRAINT FK_65D29B3219883967 FOREIGN KEY (method_id) REFERENCES payment_methods (id)");
        $this->addSql("ALTER TABLE payments ADD CONSTRAINT FK_65D29B326BF700BD FOREIGN KEY (status_id) REFERENCES status (id)");
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F056019883967");
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05602989F1FD");
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05606BF700BD");
        $this->addSql("DROP INDEX IDX_6B6F05602989F1FD ON payment_details");
        $this->addSql("DROP INDEX IDX_6B6F05606BF700BD ON payment_details");
        $this->addSql("DROP INDEX IDX_6B6F056019883967 ON payment_details");
        $this->addSql("ALTER TABLE payment_details ADD payment_id INT DEFAULT NULL, DROP method_id, DROP invoice_id, DROP status_id");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05604C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_6B6F05604C3A3BB ON payment_details (payment_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05604C3A3BB");
        $this->addSql("DROP TABLE payments");
        $this->addSql("DROP INDEX UNIQ_6B6F05604C3A3BB ON payment_details");
        $this->addSql("ALTER TABLE payment_details ADD invoice_id INT DEFAULT NULL, ADD status_id INT DEFAULT NULL, CHANGE payment_id method_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F056019883967 FOREIGN KEY (method_id) REFERENCES payment_methods (id)");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05602989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05606BF700BD FOREIGN KEY (status_id) REFERENCES status (id)");
        $this->addSql("CREATE INDEX IDX_6B6F05602989F1FD ON payment_details (invoice_id)");
        $this->addSql("CREATE INDEX IDX_6B6F05606BF700BD ON payment_details (status_id)");
        $this->addSql("CREATE INDEX IDX_6B6F056019883967 ON payment_details (method_id)");
    }
}
