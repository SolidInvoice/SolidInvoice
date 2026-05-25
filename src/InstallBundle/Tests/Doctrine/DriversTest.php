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

namespace SolidInvoice\InstallBundle\Tests\Doctrine;

use Iterator;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Doctrine\Drivers;

#[CoversClass(Drivers::class)]
final class DriversTest extends TestCase
{
    public function testGetChoiceListReturnsOnlyInstalledDrivers(): void
    {
        $choices = Drivers::getChoiceList();

        self::assertIsArray($choices);

        // Verify that all returned drivers are actually available
        $availableDrivers = PDO::getAvailableDrivers();

        foreach ($choices as $driver) {
            // MariaDB uses the mysql driver
            $actualDriver = $driver === 'mariadb' ? 'mysql' : $driver;
            self::assertContains($actualDriver, $availableDrivers, sprintf("Driver '%s' should be available", $driver));
        }
    }

    public function testGetChoiceListIncludesMariaDBWhenMySQLIsAvailable(): void
    {
        $availableDrivers = PDO::getAvailableDrivers();
        $choices = Drivers::getChoiceList();

        if (in_array('mysql', $availableDrivers, true)) {
            self::assertArrayHasKey('MySQL', $choices);
            self::assertArrayHasKey('MariaDB', $choices);
            self::assertSame('mysql', $choices['MySQL']);
            self::assertSame('mariadb', $choices['MariaDB']);
        } else {
            self::assertArrayNotHasKey('MySQL', $choices);
            self::assertArrayNotHasKey('MariaDB', $choices);
        }
    }

    public function testGetChoiceListIncludesPostgreSQLWhenAvailable(): void
    {
        $availableDrivers = PDO::getAvailableDrivers();
        $choices = Drivers::getChoiceList();

        if (in_array('pgsql', $availableDrivers, true)) {
            self::assertArrayHasKey('PostgreSQL', $choices);
            self::assertSame('pgsql', $choices['PostgreSQL']);
        } else {
            self::assertArrayNotHasKey('PostgreSQL', $choices);
        }
    }

    public function testGetChoiceListIncludesSQLiteWhenAvailable(): void
    {
        $availableDrivers = PDO::getAvailableDrivers();
        $choices = Drivers::getChoiceList();

        if (in_array('sqlite', $availableDrivers, true)) {
            self::assertArrayHasKey('Embedded Database (SQLite)', $choices);
            self::assertSame('sqlite', $choices['Embedded Database (SQLite)']);
        } else {
            self::assertArrayNotHasKey('Embedded Database (SQLite)', $choices);
        }
    }

    #[DataProvider('driverSchemeAliasProvider')]
    public function testGetDriver(string $input, string $expected): void
    {
        self::assertSame($expected, Drivers::getDriver($input));
    }

    /**
     * @return Iterator<string, array{string, string}>
     */
    public static function driverSchemeAliasProvider(): Iterator
    {
        yield 'mysql' => ['mysql', 'pdo_mysql'];
        yield 'mariadb' => ['mariadb', 'pdo_mysql'];
        yield 'postgres' => ['postgres', 'pdo_pgsql'];
        yield 'pgsql' => ['pgsql', 'pdo_pgsql'];
        yield 'sqlite' => ['sqlite', 'pdo_sqlite'];
        yield 'unknown driver returns as-is' => ['unknown', 'unknown'];
        yield 'pdo_mysql returns as-is' => ['pdo_mysql', 'pdo_mysql'];
        yield 'pdo_pgsql returns as-is' => ['pdo_pgsql', 'pdo_pgsql'];
        yield 'pdo_sqlite returns as-is' => ['pdo_sqlite', 'pdo_sqlite'];
    }
}
