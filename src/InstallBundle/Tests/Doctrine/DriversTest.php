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

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Doctrine\Drivers;

/**
 * @covers \SolidInvoice\InstallBundle\Doctrine\Drivers
 */
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
            self::assertContains($actualDriver, $availableDrivers, "Driver '{$driver}' should be available");
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
     * @return array<string, array{0: string, 1: string}>
     */
    public static function driverSchemeAliasProvider(): array
    {
        return [
            'mysql' => ['mysql', 'pdo_mysql'],
            'mariadb' => ['mariadb', 'pdo_mysql'],
            'postgres' => ['postgres', 'pdo_pgsql'],
            'pgsql' => ['pgsql', 'pdo_pgsql'],
            'sqlite' => ['sqlite', 'pdo_sqlite'],
            'unknown driver returns as-is' => ['unknown', 'unknown'],
            'pdo_mysql returns as-is' => ['pdo_mysql', 'pdo_mysql'],
            'pdo_pgsql returns as-is' => ['pdo_pgsql', 'pdo_pgsql'],
            'pdo_sqlite returns as-is' => ['pdo_sqlite', 'pdo_sqlite'],
        ];
    }
}
