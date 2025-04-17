<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Doctrine;

use PDO;
use function array_key_exists;

final class Drivers
{
    /**
     * @var array<string, string>
     */
    private static array $driverMap = [
        'mysql' => 'MySQL',
        'pgsql' => 'PostgreSQL',
        'sqlite' => 'Embedded Database (SQLite)',
        // 'mssql' => 'SQL Server',  // Not Supported
        // 'db2' => 'DB2',  // Not Supported
        // 'oci8' => 'Oracle',  // Not Supported
    ];

    /**
     * @var array<string, string>
     */
    private static array $driverSchemeAliases = [
        // 'db2' => 'ibm_db2', // Not Supported
        // 'mssql' => 'pdo_sqlsrv', // Not Supported
        // 'sqlsrv' => 'pdo_sqlsrv', // Not Supported
        'mysql' => 'pdo_mysql',
        'postgres' => 'pdo_pgsql',
        'pgsql' => 'pdo_pgsql',
        'sqlite' => 'pdo_sqlite',
    ];

    /**
     * @return array<string, string>
     */
    public static function getChoiceList(): array
    {
        $installedDrivers = PDO::getAvailableDrivers();

        $choices = [];

        foreach ($installedDrivers as $driver) {
            if (array_key_exists($driver, self::$driverMap)) {
                $choices[self::$driverMap[$driver]] = $driver;
            }
        }

        return $choices;
    }

    public static function getDriver(string $driver): string
    {
        if (array_key_exists($driver, self::$driverSchemeAliases)) {
            return self::$driverSchemeAliases[$driver];
        }

        return $driver;
    }
}
