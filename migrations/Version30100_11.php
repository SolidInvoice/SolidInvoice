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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use function sprintf;

final class Version30100_11 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Archive legacy invoices left at the removed "recurring" status so they hydrate again';
    }

    // No override of isTransactional(): this migration is a single DML statement, no DDL.
    // The neighbouring migrations turn it off for AbstractMySQLPlatform because MySQL and
    // MariaDB commit implicitly on DDL; there is no DDL here for that to defeat, so the
    // default `true` gives an atomic update on all four supported platforms.

    /**
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        // A read is safe under --dry-run, unlike executeStatement() in up() or postUp(),
        // which would run the update immediately and ignore --dry-run.
        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM invoices WHERE status = ?',
            ['recurring'],
        );

        $this->write($count === 0
            ? 'No invoices are stuck at the removed "recurring" status. Nothing to archive.'
            : sprintf('Archiving %d invoice(s) stuck at the removed "recurring" status.', $count));
    }

    public function up(Schema $schema): void
    {
        // `recurring` was removed from InvoiceStatus in 2.1.0, so a surviving row throws a
        // ValueError on hydration. `archive` was the only transition the pre-2.1 workflow
        // ever offered out of `recurring`, so this completes the one move the product
        // already sanctioned rather than inventing a new destination.
        //
        // Both columns are set together. `status` alone would leave `archived` NULL, and
        // ArchivableFilter would keep showing the row in Active Invoices wearing an
        // "Archived" status it cannot act on.
        //
        // addSql() (not executeStatement()) so the statement respects --dry-run. The value
        // is bound, not inlined, because PostgreSQL rejects `archived = 1` outright: the
        // column is a real boolean there, and only a TINYINT(1) on the MySQL family.
        $this->addSql(
            'UPDATE invoices SET status = ?, archived = ? WHERE status = ?',
            ['archived', true, 'recurring'],
            [ParameterType::STRING, ParameterType::BOOLEAN, ParameterType::STRING],
        );
    }

    public function down(Schema $schema): void
    {
        // Documented no-op, deliberately not throwIrreversibleMigrationException(): that
        // would block a legitimate down() of the surrounding release for no benefit.
        //
        // `archived` is a valid InvoiceStatus both before and after 2.1.0, so rolling the
        // code back does not strand this data. Restoring `recurring` would only bring back
        // the ValueError this migration exists to remove — you cannot un-fix a crash, and
        // the previous value is not recoverable because it was never something the current
        // application could read in the first place.
    }
}
