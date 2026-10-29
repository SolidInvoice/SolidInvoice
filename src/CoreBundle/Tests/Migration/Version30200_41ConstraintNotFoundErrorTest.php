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

use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use DoctrineMigrations\Version30200_41;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * `Version30200_41::isConstraintNotFoundError()` maps each platform's native "constraint does
 * not exist" signal onto the `DriverException` MySQL, MariaDB and PostgreSQL actually raise for
 * a `DROP CHECK`/`DROP CONSTRAINT` target that is already gone. The path it guards is reached
 * only when `constraintExists()` cannot tell the constraint is already absent, which the
 * functional migration tests cannot force through the public API — see
 * `replaceExactlyOneDocumentCheck()`'s doc block — so this isolates the predicate directly via
 * reflection instead.
 *
 * MySQL 8.0.46 confirmed `dropClause()`'s `DROP CHECK` raises `ER_CHECK_CONSTRAINT_NOT_FOUND`
 * (3821), not `ER_CONSTRAINT_NOT_FOUND` (3940) — `DROP CONSTRAINT`'s code, which this migration
 * never sends on MySQL. See SOL-395.
 *
 * @see Version30200_41
 */
final class Version30200_41ConstraintNotFoundErrorTest extends TestCase
{
    public function testMySqlErrorCode3821IsRecognisedAsConstraintNotFound(): void
    {
        self::assertTrue($this->isConstraintNotFoundError(new MySQL80Platform(), 3821, 'HY000'));
    }

    public function testMySqlErrorCode3940IsNotRecognisedAsConstraintNotFound(): void
    {
        self::assertFalse($this->isConstraintNotFoundError(new MySQL80Platform(), 3940, 'HY000'));
    }

    public function testMariaDbErrorCode1091IsRecognisedAsConstraintNotFound(): void
    {
        self::assertTrue($this->isConstraintNotFoundError(new MariaDBPlatform(), 1091, 'HY000'));
    }

    public function testPostgreSqlSqlState42704IsRecognisedAsConstraintNotFound(): void
    {
        self::assertTrue($this->isConstraintNotFoundError(new PostgreSQLPlatform(), 0, '42704'));
    }

    public function testPostgreSqlOtherSqlStateIsNotRecognisedAsConstraintNotFound(): void
    {
        self::assertFalse($this->isConstraintNotFoundError(new PostgreSQLPlatform(), 0, '42P01'));
    }

    private function isConstraintNotFoundError(AbstractPlatform $platform, int $code, string $sqlState): bool
    {
        $migration = new ReflectionClass(Version30200_41::class)->newInstanceWithoutConstructor();

        new ReflectionProperty($migration, 'platform')->setValue($migration, $platform);

        $driverException = new DriverException(
            new class($code, $sqlState) extends \Exception implements DriverExceptionInterface {
                public function __construct(
                    int $code,
                    private readonly ?string $sqlState
                ) {
                    parent::__construct('simulated driver error', $code);
                }

                public function getSQLState(): ?string
                {
                    return $this->sqlState;
                }
            },
            null,
        );

        /** @var bool $result */
        $result = new ReflectionMethod($migration, 'isConstraintNotFoundError')->invoke($migration, $driverException);

        return $result;
    }
}
