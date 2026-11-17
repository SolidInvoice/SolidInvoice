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

namespace SolidInvoice\CoreBundle\Tests\Doctrine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Doctrine\DatabaseCharsetEnvVarProcessor;

final class DatabaseCharsetEnvVarProcessorTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dsns(): iterable
    {
        yield 'mysql' => ['mysql://user:pass@127.0.0.1:3306/app', 'utf8mb4'];
        yield 'mysql2' => ['mysql2://user:pass@127.0.0.1:3306/app', 'utf8mb4'];
        yield 'mariadb' => ['mariadb://user:pass@127.0.0.1:3306/app', 'utf8mb4'];
        yield 'mysqli' => ['mysqli://user:pass@127.0.0.1:3306/app', 'utf8mb4'];
        yield 'pdo-mysql' => ['pdo-mysql://user:pass@127.0.0.1:3306/app', 'utf8mb4'];
        yield 'pdo_mysql' => ['pdo_mysql://user:pass@127.0.0.1:3306/app', 'utf8mb4'];
        yield 'uppercase scheme' => ['MySQL://user:pass@127.0.0.1:3306/app', 'utf8mb4'];
        yield 'postgresql' => ['postgresql://user:pass@127.0.0.1:5432/app', 'UTF8'];
        yield 'postgres' => ['postgres://user:pass@127.0.0.1:5432/app', 'UTF8'];
        yield 'pgsql' => ['pgsql://user:pass@127.0.0.1:5432/app', 'UTF8'];
        yield 'sqlite' => ['sqlite:///var/db.sqlite', 'UTF8'];
        yield 'no scheme' => ['not-a-dsn-at-all', 'UTF8'];
    }

    #[DataProvider('dsns')]
    public function testItResolvesTheCharsetFromTheDsnScheme(string $dsn, string $expected): void
    {
        $processor = new DatabaseCharsetEnvVarProcessor();

        $result = $processor->getEnv('db_charset', 'SOLIDINVOICE_DATABASE_URL', static fn (): string => $dsn);

        self::assertSame($expected, $result);
    }

    public function testItDeclaresItsProvidedType(): void
    {
        self::assertSame(['db_charset' => 'string'], DatabaseCharsetEnvVarProcessor::getProvidedTypes());
    }
}
