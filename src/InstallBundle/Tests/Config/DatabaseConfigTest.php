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

namespace SolidInvoice\InstallBundle\Tests\Config;

use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Config\DatabaseConfig;

final class DatabaseConfigTest extends TestCase
{
    /**
     * @param array<string, string> $params
     */
    #[DataProvider('paramsToDatabaseUrlProvider')]
    public function testParamsToDatabaseUrl(array $params, string $expected, string $expectedExceptionMessage = ''): void
    {
        if ($expectedExceptionMessage !== '') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        self::assertSame($expected, DatabaseConfig::paramsToDatabaseUrl($params));
    }

    /**
     * @return Iterator<string, array<int, array<string, int|string>|string>>
     */
    public static function paramsToDatabaseUrlProvider(): Iterator
    {
        yield 'mysql' => [
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
        ];
        yield 'postgres' => [
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
        ];
        yield 'sqlite' => [
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
        ];
        yield 'sqlite_without_path_prefix' => [
            [
                'driver' => 'sqlite',
                'name' => 'db.sqlite',
            ],
            'sqlite:///db.sqlite',
        ];
        yield 'db2' => [
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
        ];
        yield 'mssql' => [
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
        ];
        yield 'empty' => [
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
        ];
        yield 'no_user' => [
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
        ];
        yield 'no_password' => [
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
        ];
        yield 'no_user_only_password' => [
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
        ];
        yield 'no_host' => [
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
        ];
        yield 'no_port' => [
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
        ];
        yield 'no_name' => [
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
        ];
        yield 'no_version' => [
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
        ];
        yield 'no_driver' => [
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
        ];
    }
}
