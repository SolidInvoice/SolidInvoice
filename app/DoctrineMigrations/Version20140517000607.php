<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140517000607 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payments ADD details_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payments ADD CONSTRAINT FK_65D29B32BB1A0722 FOREIGN KEY (details_id) REFERENCES payment_details (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_65D29B32BB1A0722 ON payments (details_id)");
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05604C3A3BB");
        $this->addSql("DROP INDEX UNIQ_6B6F05604C3A3BB ON payment_details");
        $this->addSql("ALTER TABLE payment_details DROP payment_id");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payment_details ADD payment_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05604C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_6B6F05604C3A3BB ON payment_details (payment_id)");
        $this->addSql("ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32BB1A0722");
        $this->addSql("DROP INDEX UNIQ_65D29B32BB1A0722 ON payments");
        $this->addSql("ALTER TABLE payments DROP details_id");
    }
}
