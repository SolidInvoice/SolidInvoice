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
                    'driver' => 'mysql',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => 'test_db',
                    'version' => '5.7',
                ],
                'mysql://user:password@localhost:3306/test_db?serverVersion=5.7',
            ],
            'postgres' => [
                [
                    'driver' => 'postgres',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 5432,
                    'name' => 'test_db',
                    'version' => '13',
                ],
                'postgres://user:password@localhost:5432/test_db?serverVersion=13',
            ],
            'sqlite' => [
                [
                    'driver' => 'sqlite',
                    'user' => '',
                    'password' => '',
                    'host' => '',
                    'port' => '',
                    'name' => '/path/to/db.sqlite',
                    'version' => '',
                ],
                'sqlite:////path/to/db.sqlite',
            ],
            'sqlite_without_path_prefix' => [
                [
                    'driver' => 'sqlite',
                    'name' => 'db.sqlite',
                ],
                'sqlite:///db.sqlite',
            ],
            'db2' => [
                [
                    'driver' => 'db2',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 50000,
                    'name' => 'test_db',
                    'version' => '',
                ],
                'db2://user:password@localhost:50000/test_db?serverVersion=',
            ],
            'mssql' => [
                [
                    'driver' => 'mssql',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 1433,
                    'name' => 'test_db',
                    'version' => '',
                ],
                'mssql://user:password@localhost:1433/test_db?serverVersion=',
            ],
            'empty' => [
                [
                    'driver' => '',
                    'user' => '',
                    'password' => '',
                    'host' => '',
                    'port' => 0,
                    'name' => '',
                    'version' => '',
                ],
                '',
                'Expected one of: "db2", "mssql", "mysql", "mysql2", "postgres", "postgresql", "pgsql", "sqlite", "sqlite3". Got: ""',
            ],
            'no_user' => [
                [
                    'driver' => 'mysql',
                    'user' => '',
                    'password' => '',
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => 'test_db',
                    'version' => '',
                ],
                'mysql://localhost:3306/test_db?serverVersion=',
            ],
            'no_password' => [
                [
                    'driver' => 'mysql',
                    'user' => 'user',
                    'password' => '',
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => 'test_db',
                    'version' => '',
                ],
                'mysql://user@localhost:3306/test_db?serverVersion=',
            ],
            'no_user_only_password' => [
                [
                    'driver' => 'mysql',
                    'user' => '',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => 'test_db',
                    'version' => '',
                ],
                '',
                'Database user is required when password is set'
            ],
            'no_host' => [
                [
                    'driver' => 'mysql',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => '',
                    'port' => 3306,
                    'name' => 'test_db',
                    'version' => '',
                ],
                '',
                'Database host is required'
            ],
            'no_port' => [
                [
                    'driver' => 'mysql',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => '',
                    'name' => 'test_db',
                    'version' => '',
                ],
                'mysql://user:password@localhost/test_db?serverVersion=',
            ],
            'no_name' => [
                [
                    'driver' => 'mysql',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => '',
                    'version' => '',
                ],
                '',
                'Database name is required',
            ],
            'no_version' => [
                [
                    'driver' => 'mysql',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => 'test_db',
                    'version' => '',
                ],
                'mysql://user:password@localhost:3306/test_db?serverVersion=',
            ],
            'no_driver' => [
                [
                    'driver' => '',
                    'user' => 'user',
                    'password' => 'password',
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => 'test_db',
                    'version' => '',
                ],
                '',
                'Expected one of: "db2", "mssql", "mysql", "mysql2", "postgres", "postgresql", "pgsql", "sqlite", "sqlite3". Got: ""',
            ],
        ];
    }
}
