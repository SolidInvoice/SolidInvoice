<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version020 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE payment_details (id INT AUTO_INCREMENT NOT NULL, array LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE security_token (hash VARCHAR(255) NOT NULL, details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:object)', after_url LONGTEXT DEFAULT NULL, target_url LONGTEXT NOT NULL, payment_name VARCHAR(255) NOT NULL, PRIMARY KEY(hash)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

        $this->addSql("CREATE TABLE payment_methods (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(125) NOT NULL, payment_method VARCHAR(125) NOT NULL, settings LONGTEXT NOT NULL COMMENT '(DC2Type:array)', created DATETIME NOT NULL, updated DATETIME NOT NULL, deleted DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");

        $this->addSql("ALTER TABLE payment_details ADD invoice_id INT DEFAULT NULL, ADD status_id INT DEFAULT NULL, ADD enabled TINYINT(1) NOT NULL, ADD public TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05602989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05606BF700BD FOREIGN KEY (status_id) REFERENCES status (id)");
        $this->addSql("CREATE INDEX IDX_6B6F05602989F1FD ON payment_details (invoice_id)");
        $this->addSql("CREATE INDEX IDX_6B6F05606BF700BD ON payment_details (status_id)");

        $this->addSql("ALTER TABLE payment_details DROP public");
        $this->addSql("ALTER TABLE payment_methods ADD public TINYINT(1) NOT NULL, ADD defaultStatus_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payment_methods ADD CONSTRAINT FK_4FABF983C182F730 FOREIGN KEY (defaultStatus_id) REFERENCES status (id)");
        $this->addSql("CREATE INDEX IDX_4FABF983C182F730 ON payment_methods (defaultStatus_id)");

        $this->addSql("ALTER TABLE payment_details DROP enabled");
        $this->addSql("ALTER TABLE payment_methods ADD enabled TINYINT(1) NOT NULL");

        $this->addSql("ALTER TABLE payment_details ADD method_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F056019883967 FOREIGN KEY (method_id) REFERENCES payment_methods (id)");
        $this->addSql("CREATE INDEX IDX_6B6F056019883967 ON payment_details (method_id)");

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

        $this->addSql("ALTER TABLE payments ADD details_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payments ADD CONSTRAINT FK_65D29B32BB1A0722 FOREIGN KEY (details_id) REFERENCES payment_details (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_65D29B32BB1A0722 ON payments (details_id)");
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05604C3A3BB");
        $this->addSql("DROP INDEX UNIQ_6B6F05604C3A3BB ON payment_details");
        $this->addSql("ALTER TABLE payment_details DROP payment_id");

        $this->addSql("ALTER TABLE payments ADD message LONGTEXT DEFAULT NULL");

        $this->addSql("ALTER TABLE payments ADD completed DATETIME NOT NULL");

        $this->addSql("ALTER TABLE payments ADD client_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payments ADD CONSTRAINT FK_65D29B3219EB6921 FOREIGN KEY (client_id) REFERENCES clients (id)");
        $this->addSql("CREATE INDEX IDX_65D29B3219EB6921 ON payments (client_id)");

        $this->addSql("ALTER TABLE payments CHANGE completed completed DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE payment_details");
        $this->addSql("DROP TABLE security_token");

        $this->addSql("DROP TABLE payment_methods");

        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05602989F1FD");
        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F05606BF700BD");
        $this->addSql("DROP INDEX IDX_6B6F05602989F1FD ON payment_details");
        $this->addSql("DROP INDEX IDX_6B6F05606BF700BD ON payment_details");
        $this->addSql("ALTER TABLE payment_details DROP invoice_id, DROP status_id, DROP enabled, DROP public");

        $this->addSql("ALTER TABLE payment_details ADD public TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE payment_methods DROP FOREIGN KEY FK_4FABF983C182F730");
        $this->addSql("DROP INDEX IDX_4FABF983C182F730 ON payment_methods");
        $this->addSql("ALTER TABLE payment_methods DROP public, DROP defaultStatus_id");

        $this->addSql("ALTER TABLE payment_details ADD enabled TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE payment_methods DROP enabled");

        $this->addSql("ALTER TABLE payment_details DROP FOREIGN KEY FK_6B6F056019883967");
        $this->addSql("DROP INDEX IDX_6B6F056019883967 ON payment_details");
        $this->addSql("ALTER TABLE payment_details DROP method_id");

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

        $this->addSql("ALTER TABLE payment_details ADD payment_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE payment_details ADD CONSTRAINT FK_6B6F05604C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_6B6F05604C3A3BB ON payment_details (payment_id)");
        $this->addSql("ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32BB1A0722");
        $this->addSql("DROP INDEX UNIQ_65D29B32BB1A0722 ON payments");
        $this->addSql("ALTER TABLE payments DROP details_id");

        $this->addSql("ALTER TABLE payments DROP message");

        $this->addSql("ALTER TABLE payments DROP completed");

        $this->addSql("ALTER TABLE payments DROP FOREIGN KEY FK_65D29B3219EB6921");
        $this->addSql("DROP INDEX IDX_65D29B3219EB6921 ON payments");
        $this->addSql("ALTER TABLE payments DROP client_id");

        $this->addSql("ALTER TABLE payments CHANGE completed completed DATETIME NOT NULL");
    }
}
