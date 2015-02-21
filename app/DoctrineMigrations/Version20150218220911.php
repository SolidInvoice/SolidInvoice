<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150218220911 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE client_credit (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, value DOUBLE PRECISION NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_4967254D19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_credit ADD CONSTRAINT FK_4967254D19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FABF9837B61A1F6 ON payment_methods (payment_method)');

        $this->addSql('INSERT INTO payment_methods (id, name, settings, created, updated, deleted, internal, enabled, payment_method) VALUES (NULL, "Credit", "a:0:{}", NOW(), NOW(), NULL, 1, 1, "credit")');
        $this->addSql('ALTER TABLE invoices ADD balance DOUBLE PRECISION NOT NULL');

        $this->addSql('INSERT INTO client_credit (client_id, value, created, updated, deleted) SELECT id, 0, NOW(), NOW(), null from clients');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE client_credit');
    }
}
