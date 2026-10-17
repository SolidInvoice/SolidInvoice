<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version30100_21 extends AbstractMigration
{
    private const string OLD_TABLE = 'recurringinvoice_contact';

    private const string NEW_TABLE = 'recurringinvoice_contacts';

    public function getDescription(): string
    {
        return 'Rename recurringinvoice_contact to recurringinvoice_contacts to match the RecurringInvoice::$users join table mapping, keeping its rows, indexes and foreign keys';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * doctrine:schema:update's diff cannot see a rename: comparing the two table names on their
     * own, it would emit `CREATE TABLE recurringinvoice_contacts` plus
     * `DROP TABLE recurringinvoice_contact`, deleting every recurring-invoice/contact
     * association. Every statement here is raw SQL against the real table, so it is renamed in
     * place rather than recreated. The index and foreign key names below are Doctrine's own
     * `_generateIdentifierName()` output for each table/column pair (`fk`/`idx` + the hex CRC32
     * of the table name followed by the hex CRC32 of the column name) - not invented names.
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::NEW_TABLE) || ! $schema->hasTable(self::OLD_TABLE)) {
            return;
        }

        if ($this->platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE recurringinvoice_contact DROP CONSTRAINT fk_1673913ee31ccdf');
            $this->addSql('ALTER TABLE recurringinvoice_contact DROP CONSTRAINT fk_1673913ee7a1254a');
            $this->addSql('ALTER TABLE recurringinvoice_contact RENAME TO recurringinvoice_contacts');
            $this->addSql('ALTER INDEX idx_1673913ee31ccdf RENAME TO idx_da79a131e31ccdf');
            $this->addSql('ALTER INDEX idx_1673913ee7a1254a RENAME TO idx_da79a131e7a1254a');
            $this->addSql('ALTER TABLE recurringinvoice_contacts ADD CONSTRAINT fk_da79a131e31ccdf FOREIGN KEY (recurringinvoice_id) REFERENCES recurring_invoices (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE recurringinvoice_contacts ADD CONSTRAINT fk_da79a131e7a1254a FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');

            return;
        }

        $this->addSql('ALTER TABLE recurringinvoice_contact DROP FOREIGN KEY FK_1673913EE31CCDF');
        $this->addSql('ALTER TABLE recurringinvoice_contact DROP FOREIGN KEY FK_1673913EE7A1254A');
        $this->addSql('RENAME TABLE recurringinvoice_contact TO recurringinvoice_contacts');
        $this->addSql('ALTER TABLE recurringinvoice_contacts RENAME INDEX IDX_1673913EE31CCDF TO IDX_DA79A131E31CCDF');
        $this->addSql('ALTER TABLE recurringinvoice_contacts RENAME INDEX IDX_1673913EE7A1254A TO IDX_DA79A131E7A1254A');
        $this->addSql('ALTER TABLE recurringinvoice_contacts ADD CONSTRAINT FK_DA79A131E31CCDF FOREIGN KEY (recurringinvoice_id) REFERENCES recurring_invoices (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recurringinvoice_contacts ADD CONSTRAINT FK_DA79A131E7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::OLD_TABLE) || ! $schema->hasTable(self::NEW_TABLE)) {
            return;
        }

        if ($this->platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE recurringinvoice_contacts DROP CONSTRAINT fk_da79a131e31ccdf');
            $this->addSql('ALTER TABLE recurringinvoice_contacts DROP CONSTRAINT fk_da79a131e7a1254a');
            $this->addSql('ALTER TABLE recurringinvoice_contacts RENAME TO recurringinvoice_contact');
            $this->addSql('ALTER INDEX idx_da79a131e31ccdf RENAME TO idx_1673913ee31ccdf');
            $this->addSql('ALTER INDEX idx_da79a131e7a1254a RENAME TO idx_1673913ee7a1254a');
            $this->addSql('ALTER TABLE recurringinvoice_contact ADD CONSTRAINT fk_1673913ee31ccdf FOREIGN KEY (recurringinvoice_id) REFERENCES recurring_invoices (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE recurringinvoice_contact ADD CONSTRAINT fk_1673913ee7a1254a FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');

            return;
        }

        $this->addSql('ALTER TABLE recurringinvoice_contacts DROP FOREIGN KEY FK_DA79A131E31CCDF');
        $this->addSql('ALTER TABLE recurringinvoice_contacts DROP FOREIGN KEY FK_DA79A131E7A1254A');
        $this->addSql('RENAME TABLE recurringinvoice_contacts TO recurringinvoice_contact');
        $this->addSql('ALTER TABLE recurringinvoice_contact RENAME INDEX IDX_DA79A131E31CCDF TO IDX_1673913EE31CCDF');
        $this->addSql('ALTER TABLE recurringinvoice_contact RENAME INDEX IDX_DA79A131E7A1254A TO IDX_1673913EE7A1254A');
        $this->addSql('ALTER TABLE recurringinvoice_contact ADD CONSTRAINT FK_1673913EE31CCDF FOREIGN KEY (recurringinvoice_id) REFERENCES recurring_invoices (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recurringinvoice_contact ADD CONSTRAINT FK_1673913EE7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');
    }
}
