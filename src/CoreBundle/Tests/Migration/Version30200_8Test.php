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
use DoctrineMigrations\Version30200_8;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Runs against whichever engine the suite is on, so db-tests.yml covers MySQL, MariaDB and PostgreSQL.
 */
final class Version30200_8Test extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testAddsTheMissingMethodsOnceToEveryCompany(): void
    {
        $other = CompanyFactory::createOne();
        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);

        // As if this company predates the two methods.
        $connection->executeStatement(
            'DELETE FROM payment_methods WHERE company_id = ? AND gateway_name IN (?, ?)',
            [$this->company->getId(), 'cheque', 'card_terminal'],
            [UlidType::NAME],
        );

        $this->migrate($connection);
        $this->migrate($connection);

        foreach ([$this->company->getId(), $other->getId()] as $companyId) {
            $rows = $connection->fetchAllAssociative(
                'SELECT gateway_name, factory, enabled FROM payment_methods WHERE company_id = ? AND gateway_name IN (?, ?) ORDER BY gateway_name',
                [$companyId, 'card_terminal', 'cheque'],
                [UlidType::NAME],
            );

            self::assertSame(['card_terminal', 'cheque'], array_column($rows, 'gateway_name'));
            self::assertSame(['offline', 'offline'], array_column($rows, 'factory'));
            self::assertEquals([true, true], array_map(boolval(...), array_column($rows, 'enabled')));
        }
    }

    private function migrate(Connection $connection): void
    {
        $migration = new Version30200_8($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
