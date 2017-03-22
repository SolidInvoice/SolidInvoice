<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Version110 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $currency = $this->container->getParameter('currency');

        $this->addSql('ALTER TABLE clients ADD currency VARCHAR(3) DEFAULT NULL');

        $this->addSql('ALTER TABLE client_credit ADD value_currency VARCHAR(3) DEFAULT NULL, CHANGE value value_amount INT NOT NULL');
        $this->addSql('ALTER TABLE invoices
          CHANGE total total_amount INT NOT NULL,
          ADD total_currency VARCHAR(3) DEFAULT NULL,
  
          CHANGE base_total baseTotal_amount INT NOT NULL,
          ADD baseTotal_currency VARCHAR(3) DEFAULT NULL,
          
          CHANGE balance balance_amount INT NOT NULL,
          ADD balance_currency VARCHAR(3) DEFAULT NULL,
          
          CHANGE tax tax_amount INT NOT NULL,
          ADD tax_currency VARCHAR(3) DEFAULT NULL');

        $this->addSql('ALTER TABLE invoice_lines CHANGE price price_amount INT NOT NULL, ADD price_currency VARCHAR(3) DEFAULT NULL, CHANGE total total_amount INT NOT NULL, ADD total_currency VARCHAR(3) DEFAULT NULL');

        $this->addSql('ALTER TABLE quote_lines
          CHANGE price price_amount INT NOT NULL,
          ADD price_currency VARCHAR(3) DEFAULT NULL,
          
          CHANGE total total_amount INT NOT NULL,
          ADD total_currency VARCHAR(3) DEFAULT NULL');

        $this->addSql('ALTER TABLE quotes
          CHANGE total total_amount INT NOT NULL,
          ADD total_currency VARCHAR(3) DEFAULT NULL,
          
          CHANGE base_total baseTotal_amount INT NOT NULL,
          ADD baseTotal_currency VARCHAR(3) DEFAULT NULL,
          
          CHANGE tax tax_amount INT NOT NULL,
          ADD tax_currency VARCHAR(3) DEFAULT NULL');

        $this->addSql('UPDATE client_credit SET value_currency = :currency', ['currency' => $currency]);

        $this->addSql('UPDATE invoices SET total_currency = :currency, baseTotal_currency = :currency, balance_currency = :currency, tax_currency = :currency', ['currency' => $currency]);
        $this->addSql('UPDATE invoice_lines SET price_currency = :currency, total_currency = :currency', ['currency' => $currency]);

        $this->addSql('UPDATE quotes SET total_currency = :currency, baseTotal_currency = :currency, tax_currency = :currency', ['currency' => $currency]);
        $this->addSql('UPDATE quote_lines SET price_currency = :currency, total_currency = :currency', ['currency' => $currency]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE client_credit DROP value_currency, CHANGE value_amount value INT NOT NULL');

        $this->addSql('ALTER TABLE invoices
          CHANGE total_amount total INT NOT NULL,
          DROP total_currency,
  
          CHANGE baseTotal_amount base_total INT NOT NULL,
          DROP baseTotal_currency,
          
          CHANGE balance_amount balance INT NOT NULL,
          DROP balance_currency,
          
          CHANGE tax_amount tax  INT NOT NULL,
          DROP tax_currency');

        $this->addSql('ALTER TABLE invoice_lines CHANGE price_amount price INT NOT NULL, CHANGE total_amount total INT NOT NULL, DROP price_currency, DROP total_currency');

        $this->addSql('ALTER TABLE quote_lines
          CHANGE price_amount price INT NOT NULL,
          CHANGE total_amount total INT NOT NULL,
          DROP price_currency,
          DROP total_currency');

        $this->addSql('ALTER TABLE quotes
          CHANGE total_amount total INT NOT NULL,
          CHANGE baseTotal_amount base_total INT NOT NULL,
          CHANGE tax_amount tax INT DEFAULT NULL,
          DROP total_currency,
          DROP baseTotal_currency,
          DROP tax_currency');

        $this->addSql('ALTER TABLE clients DROP currency');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_33401573E7927C7419EB69212 ON contacts (id)');
    }
}
