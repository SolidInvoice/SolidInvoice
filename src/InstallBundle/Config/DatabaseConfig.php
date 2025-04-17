<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Config;

use Webmozart\Assert\Assert;
use function array_key_exists;
use function sprintf;

final class DatabaseConfig
{
    /**
     * @var list<string>
     */
    private static array $driverSchemeAliases = [
        'db2',
        'mssql',
        'mysql',
        'mysql2',
        'postgres',
        'postgresql',
        'pgsql',
        'sqlite',
        'sqlite3',
    ];

    /**
     * @param array<string, mixed> $params
     */
    public static function paramsToDatabaseUrl(array $params): string
    {
        Assert::keyExists($params, 'driver');
        Assert::inArray($params['driver'], self::$driverSchemeAliases);

        Assert::keyExists($params, 'name', 'Database name is required');
        Assert::stringNotEmpty($params['name'], 'Database name is required');

        if ($params['driver'] === 'sqlite') {
            return sprintf(
                '%s:///%s',
                $params['driver'],
                $params['name']
            );
        }

        Assert::keyExists($params, 'host', 'Database host is required');
        Assert::stringNotEmpty($params['host'], 'Database host is required');

        if (array_key_exists('password', $params) && $params['password'] !== '' && $params['password'] !== null) {
            Assert::keyExists($params, 'user', 'Database user is required when password is set');
            Assert::stringNotEmpty($params['user'], 'Database user is required when password is set');
        }

        return sprintf(
            '%s://%s%s%s%s%s/%s?serverVersion=%s',
            $params['driver'],
            $params['user'] ?? '',
            ($params['password'] ?? '') !== '' ? ':' . $params['password'] : '',
            ($params['user'] ?? '') !== '' ? '@' : '',
            $params['host'],
            (string) ($params['port'] ?? '') !== '' ? ':' . $params['port'] : '',
            $params['name'],
            $params['version'] ?? ''
        );
    }
}
