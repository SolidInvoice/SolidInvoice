<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Tests\Config;

use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Config\DatabaseConfig;

final class DatabaseConfigTest extends TestCase
{
    /**
     * @dataProvider paramsToDatabaseUrlProvider
     *
     * @param array<string, string> $params
     */
    public function testParamsToDatabaseUrl(array $params, string $expected, string $expectedExceptionMessage = ''): void
    {
        if ($expectedExceptionMessage !== '') {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        self::assertSame($expected, DatabaseConfig::paramsToDatabaseUrl($params));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function paramsToDatabaseUrlProvider(): array
    {
        return [
            'mysql' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 3306,
                    'database_name' => 'test_db',
                    'database_version' => '5.7',
                ],
                'mysql://user:password@localhost:3306/test_db?serverVersion=5.7',
            ],
            'postgres' => [
                [
                    'database_driver' => 'pdo_pgsql',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 5432,
                    'database_name' => 'test_db',
                    'database_version' => '13',
                ],
                'postgres://user:password@localhost:5432/test_db?serverVersion=13',
            ],
            'sqlite' => [
                [
                    'database_driver' => 'pdo_sqlite',
                    'database_user' => '',
                    'database_password' => '',
                    'database_host' => '',
                    'database_port' => '',
                    'database_name' => '/path/to/db.sqlite',
                    'database_version' => '',
                ],
                'sqlite:////path/to/db.sqlite',
            ],
            'sqlite_without_path_prefix' => [
                [
                    'database_driver' => 'pdo_sqlite',
                    'database_name' => 'db.sqlite',
                ],
                'sqlite:///db.sqlite',
            ],
            'db2' => [
                [
                    'database_driver' => 'ibm_db2',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 50000,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                'db2://user:password@localhost:50000/test_db?serverVersion=',
            ],
            'mssql' => [
                [
                    'database_driver' => 'pdo_sqlsrv',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 1433,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                'mssql://user:password@localhost:1433/test_db?serverVersion=',
            ],
            'empty' => [
                [
                    'database_driver' => '',
                    'database_user' => '',
                    'database_password' => '',
                    'database_host' => '',
                    'database_port' => 0,
                    'database_name' => '',
                    'database_version' => '',
                ],
                '',
                'Expected one of: "ibm_db2", "pdo_sqlsrv", "pdo_mysql", "pdo_pgsql", "pdo_sqlite". Got: ""',
            ],
            'no_user' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => '',
                    'database_password' => '',
                    'database_host' => 'localhost',
                    'database_port' => 3306,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                'mysql://localhost:3306/test_db?serverVersion=',
            ],
            'no_password' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => 'user',
                    'database_password' => '',
                    'database_host' => 'localhost',
                    'database_port' => 3306,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                'mysql://user@localhost:3306/test_db?serverVersion=',
            ],
            'no_user_only_password' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => '',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 3306,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                '',
                'Database user is required when password is set'
            ],
            'no_host' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => '',
                    'database_port' => 3306,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                '',
                'Database host is required'
            ],
            'no_port' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => '',
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                'mysql://user:password@localhost/test_db?serverVersion=',
            ],
            'no_name' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 3306,
                    'database_name' => '',
                    'database_version' => '',
                ],
                '',
                'Database name is required',
            ],
            'no_version' => [
                [
                    'database_driver' => 'pdo_mysql',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 3306,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                'mysql://user:password@localhost:3306/test_db?serverVersion=',
            ],
            'no_driver' => [
                [
                    'database_driver' => '',
                    'database_user' => 'user',
                    'database_password' => 'password',
                    'database_host' => 'localhost',
                    'database_port' => 3306,
                    'database_name' => 'test_db',
                    'database_version' => '',
                ],
                '',
                'Expected one of: "ibm_db2", "pdo_sqlsrv", "pdo_mysql", "pdo_pgsql", "pdo_sqlite". Got: ""',
            ],
        ];
    }
}
