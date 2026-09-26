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

namespace SolidInvoice\CoreBundle\Doctrine;

use Closure;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use function explode;
use function in_array;
use function str_contains;
use function str_replace;
use function strtolower;

/**
 * Resolves the DBAL connection charset from the database URL scheme. MySQL and MariaDB
 * need `utf8mb4`; `UTF8` is a deprecated 3-byte alias on those drivers. PostgreSQL and
 * SQLite need the literal `UTF8` that DBAL already passes to their drivers, so every
 * other scheme, including an unparseable one, falls back to it.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Doctrine\DatabaseCharsetEnvVarProcessorTest
 */
final class DatabaseCharsetEnvVarProcessor implements EnvVarProcessorInterface
{
    private const string UTF8MB4 = 'utf8mb4';

    private const string UTF8 = 'UTF8';

    /**
     * `mysql` and `mysql2` are DoctrineBundle's own scheme aliases
     * (ConnectionFactory::DEFAULT_SCHEME_MAP). `mariadb`, `mysqli` and `pdo_mysql` are
     * driver names DBAL's DsnParser accepts as a scheme unchanged, after it lowercases
     * the scheme and turns `-` into `_` (so `pdo-mysql` normalizes to `pdo_mysql` too).
     *
     * @var list<string>
     */
    private const array MYSQL_FAMILY = [
        'mysql',
        'mysql2',
        'mariadb',
        'mysqli',
        'pdo_mysql',
    ];

    public function getEnv(string $prefix, string $name, Closure $getEnv): string
    {
        $dsn = $getEnv($name);

        if (! str_contains($dsn, '://')) {
            return self::UTF8;
        }

        $scheme = strtolower(str_replace('-', '_', explode('://', $dsn, 2)[0]));

        return in_array($scheme, self::MYSQL_FAMILY, true) ? self::UTF8MB4 : self::UTF8;
    }

    /**
     * @return array<string, string>
     */
    public static function getProvidedTypes(): array
    {
        return ['db_charset' => 'string'];
    }
}
