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
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;

final class Version30200_1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the company legal identity columns and the tax identifier scheme column';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $companies = $schema->getTable(Company::TABLE_NAME);

        if (! $companies->hasColumn('legal_name')) {
            $companies->addColumn('legal_name', Types::STRING, ['length' => 255, 'notnull' => false]);
        }

        if (! $companies->hasColumn('registration_number')) {
            $companies->addColumn('registration_number', Types::STRING, ['length' => 64, 'notnull' => false]);
        }

        if (! $companies->hasColumn('legal_form')) {
            $companies->addColumn('legal_form', Types::STRING, ['length' => 255, 'notnull' => false]);
        }

        if (! $companies->hasColumn('additional_legal_info')) {
            $companies->addColumn('additional_legal_info', Types::TEXT, ['notnull' => false]);
        }

        $taxIdentifiers = $schema->getTable(TaxIdentifier::TABLE_NAME);

        if (! $taxIdentifiers->hasColumn('scheme')) {
            $taxIdentifiers->addColumn('scheme', Types::STRING, ['length' => 32, 'notnull' => false]);
        }
    }

    /**
     * The executor runs explicit addSql() statements from up() before the schema-diff SQL
     * that creates the "scheme" column, so the backfill has to happen here instead, once the
     * column is guaranteed to exist.
     *
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        // Existing rows labelled 'VAT' were picked from a closed choice list, so they mean
        // what they say. Only NULL schemes are written, so a user's own choice is never
        // overwritten.
        $this->connection->executeStatement(
            sprintf(
                "UPDATE %s SET scheme = 'vat' WHERE scheme IS NULL AND UPPER(label) = 'VAT'",
                TaxIdentifier::TABLE_NAME,
            ),
        );
    }

    public function down(Schema $schema): void
    {
        $companies = $schema->getTable(Company::TABLE_NAME);

        foreach (['legal_name', 'registration_number', 'legal_form', 'additional_legal_info'] as $column) {
            if ($companies->hasColumn($column)) {
                $companies->dropColumn($column);
            }
        }

        $taxIdentifiers = $schema->getTable(TaxIdentifier::TABLE_NAME);

        if ($taxIdentifiers->hasColumn('scheme')) {
            $taxIdentifiers->dropColumn('scheme');
        }
    }
}
