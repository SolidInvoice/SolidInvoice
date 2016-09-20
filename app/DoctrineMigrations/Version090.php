<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version090 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE contacts ADD email VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX email ON contacts (email)');

        $this->addSql(<<<'MYSQL'
            UPDATE
              contacts c
            SET
              c.email = (
                SELECT
                  value
                FROM
                  contact_details cd
                WHERE
                  contact_type_id = (
                    SELECT
                      id
                    FROM
                      contact_types
                    WHERE
                      name = "email" LIMIT 1
                  )
                and cd.contact_id = c.id
              )
MYSQL
        );

        $this->addSql('DELETE FROM contact_details WHERE detail_type = "primary"');

        $this->addSql('ALTER TABLE contact_details DROP detail_type');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX email ON contacts');
        $this->addSql('ALTER TABLE contacts DROP email');
        $this->addSql('ALTER TABLE contact_details ADD detail_type VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
