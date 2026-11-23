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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use DoctrineMigrations\Version30000_9;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * MariaDBPlatform is a sibling of MySQLPlatform, not a subclass. A check that tests
 * `instanceof MySQLPlatform` treats MariaDB as transactional DDL, but MariaDB commits
 * implicitly on DDL like MySQL does, so the transaction would be a fiction.
 */
final class Version30000_9TransactionalTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @return iterable<string, array{AbstractPlatform, bool}>
     */
    public static function platforms(): iterable
    {
        yield 'MySQL' => [new MySQLPlatform(), false];
        yield 'MariaDB' => [new MariaDBPlatform(), false];
        yield 'Oracle' => [new OraclePlatform(), false];
        yield 'PostgreSQL' => [new PostgreSQLPlatform(), true];
    }

    #[DataProvider('platforms')]
    public function testIsTransactional(AbstractPlatform $platform, bool $expected): void
    {
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->andReturn($platform);
        $connection->shouldReceive('createSchemaManager')->andReturn(M::mock(AbstractSchemaManager::class));

        $migration = new Version30000_9($connection, new NullLogger());

        self::assertSame($expected, $migration->isTransactional());
    }
}
