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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version30100_12 extends AbstractMigration
{
    private const array TABLES = ['invoices', 'quotes', 'recurring_invoices'];

    public function getDescription(): string
    {
        return 'Record which tax arithmetic each invoice and quote was issued under, '
            . 'so an issued document is never recalculated under a later one';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform
            && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn('tax_arithmetic_version')) {
                $table->addColumn('tax_arithmetic_version', Types::SMALLINT, ['notnull' => false]);
            }
        }
    }

    /**
     * Every invoice and quote that is past draft is pinned to version 1, the arithmetic it
     * was issued under. `recurring_invoices` is left untouched: a template is never issued.
     *
     * The literals are the backed values of {@see \SolidInvoice\InvoiceBundle\Enum\InvoiceStatus}
     * and {@see \SolidInvoice\QuoteBundle\Enum\QuoteStatus}, spelled out rather than imported, so
     * this migration keeps producing the result it produced the day it was written even if the
     * runtime enum changes later.
     *
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        foreach (['invoices', 'quotes'] as $table) {
            $this->connection->createQueryBuilder()
                ->update($table)
                ->set('tax_arithmetic_version', ':version')
                ->where('status NOT IN (:statuses)')
                ->setParameter('version', 1)
                ->setParameter('statuses', ['new', 'draft'], ArrayParameterType::STRING)
                ->executeStatement();
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('tax_arithmetic_version')) {
                $table->dropColumn('tax_arithmetic_version');
            }
        }
    }
}
