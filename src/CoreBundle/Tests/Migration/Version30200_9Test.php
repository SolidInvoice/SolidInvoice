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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version30200_9;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function array_column;

/**
 * Runs against whichever engine the suite is on, so db-tests.yml covers MySQL, MariaDB and PostgreSQL.
 */
final class Version30200_9Test extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testAddsTheMissingSettingsOnceToEveryCompanyAndKeepsExistingValues(): void
    {
        $other = CompanyFactory::createOne();
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        // As if this company predates the settings, except for one it already changed.
        $connection->executeStatement(
            "DELETE FROM app_config WHERE company_id = ? AND setting_key IN ('invoice/default_terms', 'invoice/default_notes', 'quote/default_terms', 'quote/default_notes')",
            [$this->company->getId()],
            [UlidType::NAME],
        );
        $connection->executeStatement(
            "UPDATE app_config SET setting_value = '14' WHERE company_id = ? AND setting_key = 'quote/validity_days'",
            [$this->company->getId()],
            [UlidType::NAME],
        );

        $this->migrate($connection);
        $this->migrate($connection);

        foreach ([$this->company->getId(), $other->getId()] as $companyId) {
            $rows = $connection->fetchAllAssociative(
                "SELECT setting_key, setting_value FROM app_config WHERE company_id = ? AND setting_key IN ('invoice/default_terms', 'invoice/default_notes', 'quote/default_terms', 'quote/default_notes', 'quote/validity_days') ORDER BY setting_key",
                [$companyId],
                [UlidType::NAME],
            );

            self::assertSame(['invoice/default_notes', 'invoice/default_terms', 'quote/default_notes', 'quote/default_terms', 'quote/validity_days'], array_column($rows, 'setting_key'));
        }

        self::assertSame('14', $connection->fetchOne(
            "SELECT setting_value FROM app_config WHERE company_id = ? AND setting_key = 'quote/validity_days'",
            [$this->company->getId()],
            [UlidType::NAME],
        ));
    }

    private function migrate(Connection $connection): void
    {
        $migration = new Version30200_9($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
