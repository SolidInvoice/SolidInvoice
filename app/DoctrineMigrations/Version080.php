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
class Version080 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_4FABF9837B61A1F6 ON payment_methods');
        $this->addSql('ALTER TABLE payment_methods ADD factory VARCHAR(125) NOT NULL, CHANGE payment_method gateway_name VARCHAR(125) NOT NULL, CHANGE settings config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FABF9833D4E91C8 ON payment_methods (gateway_name)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP INDEX UNIQ_4FABF9833D4E91C8 ON payment_methods');
        $this->addSql('ALTER TABLE payment_methods ADD payment_method VARCHAR(125) NOT NULL COLLATE utf8_unicode_ci, DROP gateway_name, DROP factory, CHANGE config settings LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:array)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FABF9837B61A1F6 ON payment_methods (payment_method)');
    }
}
