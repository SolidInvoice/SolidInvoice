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
class Version200 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users DROP locked, DROP expired, DROP expires_at, DROP credentials_expired, DROP credentials_expire_at, CHANGE username username VARCHAR(180) NOT NULL, CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE username_canonical username_canonical VARCHAR(180) NOT NULL, CHANGE email_canonical email_canonical VARCHAR(180) NOT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9C05FB297 ON users (confirmation_token)');

        $this->addSql('UPDATE tax_rates SET rate = rate * 100');
        $this->addSql('UPDATE tax_rates SET tax_type = "Inclusive" WHERE tax_type = "inclusive"');
        $this->addSql('UPDATE tax_rates SET tax_type = "Exlusive" WHERE tax_type = "exlusive"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_1483A5E9C05FB297 ON users');
        $this->addSql('ALTER TABLE users ADD locked TINYINT(1) NOT NULL, ADD expired TINYINT(1) NOT NULL, ADD expires_at DATETIME DEFAULT NULL, ADD credentials_expired TINYINT(1) NOT NULL, ADD credentials_expire_at DATETIME DEFAULT NULL, CHANGE username username VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE username_canonical username_canonical VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email_canonical email_canonical VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE salt salt VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE confirmation_token confirmation_token VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');

        $this->addSql('UPDATE tax_rates SET rate = rate / 100');
        $this->addSql('UPDATE tax_rates SET tax_type = "inclusive" WHERE tax_type = "Inclusive"');
        $this->addSql('UPDATE tax_rates SET tax_type = "exlusive" WHERE tax_type = "Exlusive"');
    }
}
