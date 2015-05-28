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

use CSBill\PaymentBundle\Model\Status;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150218161936 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B326BF700BD');
        $this->addSql('DROP INDEX IDX_65D29B326BF700BD ON payments');
        $this->addSql('ALTER TABLE payments CHANGE status_id status VARCHAR(25) NOT NULL');

        $this->addSql(
            sprintf(
                "UPDATE payments SET status = CASE status
                  WHEN 3 THEN '%s'
                  WHEN 4 THEN '%s'
                  WHEN 5 THEN '%s'
                  WHEN 6 THEN '%s'
                  WHEN 7 THEN '%s'
                  WHEN 8 THEN '%s'
                  WHEN 9 THEN '%s' END",
                Status::STATUS_UNKNOWN,
                Status::STATUS_FAILED,
                Status::STATUS_SUSPENDED,
                Status::STATUS_EXPIRED,
                Status::STATUS_CAPTURED,
                Status::STATUS_PENDING,
                Status::STATUS_CANCELLED
            )
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            sprintf(
                "UPDATE payments SET status = CASE status
                  WHEN '%s' THEN 3
                  WHEN '%s' THEN 4
                  WHEN '%s' THEN 5
                  WHEN '%s' THEN 6
                  WHEN '%s' THEN 7
                  WHEN '%s' THEN 8
                  WHEN '%s' THEN 9 END",
                Status::STATUS_UNKNOWN,
                Status::STATUS_FAILED,
                Status::STATUS_SUSPENDED,
                Status::STATUS_EXPIRED,
                Status::STATUS_CAPTURED,
                Status::STATUS_PENDING,
                Status::STATUS_CANCELLED
            )
        );

        $this->addSql('ALTER TABLE payments CHANGE status status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B326BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('CREATE INDEX IDX_65D29B326BF700BD ON payments (status_id)');
    }
}
