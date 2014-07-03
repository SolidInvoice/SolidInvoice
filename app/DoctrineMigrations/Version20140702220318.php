<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140702220318 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payments ADD client_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payments ADD CONSTRAINT FK_65D29B3219EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)");
        $this->addSql("CREATE INDEX IDX_65D29B3219EB6921 ON payments (client_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE payments DROP FOREIGN KEY FK_65D29B3219EB6921");
        $this->addSql("DROP INDEX IDX_65D29B3219EB6921 ON payments");
        $this->addSql("ALTER TABLE payments DROP client_id");
    }
}
