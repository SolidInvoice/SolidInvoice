<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150216221835 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment_methods DROP FOREIGN KEY FK_4FABF983C182F730');
        $this->addSql('DROP INDEX IDX_4FABF983C182F730 ON payment_methods');
        $this->addSql('ALTER TABLE payment_methods DROP defaultStatus_id, CHANGE public internal TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment_methods ADD defaultStatus_id INT DEFAULT NULL, CHANGE internal public TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_methods ADD CONSTRAINT FK_4FABF983C182F730 FOREIGN KEY (defaultStatus_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_4FABF983C182F730 ON payment_methods (defaultStatus_id)');
    }
}
