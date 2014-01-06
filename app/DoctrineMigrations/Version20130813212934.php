<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130813212934 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE ext_log_entries CHANGE object_id object_id VARCHAR(64) DEFAULT NULL");
        $this->addSql("CREATE INDEX log_version_lookup_idx ON ext_log_entries (object_id, object_class, version)");
        $this->addSql("ALTER TABLE quotes ADD uuid VARCHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql("ALTER TABLE invoices ADD uuid VARCHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX log_version_lookup_idx ON ext_log_entries");
        $this->addSql("ALTER TABLE ext_log_entries CHANGE object_id object_id VARCHAR(32) DEFAULT NULL");
        $this->addSql("ALTER TABLE invoices DROP uuid");
        $this->addSql("ALTER TABLE quotes DROP uuid");
    }
}
