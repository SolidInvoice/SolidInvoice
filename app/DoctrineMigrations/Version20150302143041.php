<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use CSBill\ClientBundle\Model\Status;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150302143041 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E746BF700BD');
        $this->addSql('DROP TABLE status');
        $this->addSql('DROP INDEX IDX_C82E746BF700BD ON clients');
        $this->addSql('ALTER TABLE clients CHANGE status_id status VARCHAR(25) NOT NULL');

        $this->addSql(
            sprintf(
                "UPDATE clients SET status = CASE status
                  WHEN 1 THEN '%s'
                  WHEN 2 THEN '%s'END",
                Status::STATUS_ACTIVE,
                Status::STATUS_INACTIVE
            )
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            sprintf(
                "UPDATE clients SET status = CASE status
                  WHEN '%s' THEN 1
                  WHEN '%s' THEN 2 END",
                Status::STATUS_ACTIVE,
                Status::STATUS_INACTIVE
            )
        );

        $this->addSql('CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(125) NOT NULL, `label` VARCHAR(125) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, entity VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clients CHANGE status status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E746BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_C82E746BF700BD ON clients (status_id)');
    }
}
