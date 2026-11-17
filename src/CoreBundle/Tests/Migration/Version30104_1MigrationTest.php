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
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use DoctrineMigrations\Version30102_4;
use DoctrineMigrations\Version30104_1;
use Symfony\Component\Uid\Ulid;
use function ini_get;
use function putenv;

/**
 * The whole lineage runs with a PostgreSQL session in America/New_York (PGTZ is inherited by the
 * bin/console processes too), so a conversion without the USING clause would shift the seeded
 * value by the session's offset and fail the assertion.
 */
final class Version30104_1MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    private const string STORED = '2026-10-15 20:58:09';

    protected function setUp(): void
    {
        putenv('PGTZ=America/New_York');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('PGTZ');

        parent::tearDown();
    }

    protected static function migrationClass(): string
    {
        return Version30104_1::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30102_4::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return ['TYPE TIMESTAMP(0) WITH TIME ZONE'];
    }

    protected function seedUpgradedLineage(Connection $connection): void
    {
        if (! $connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return;
        }

        $companyId = new Ulid()->toRfc4122();

        $connection->insert('companies', ['id' => $companyId, 'name' => 'Acme']);
        $connection->insert('tax_rates', [
            'id' => new Ulid()->toRfc4122(),
            'company_id' => $companyId,
            'name' => 'VAT',
            'rate' => 15,
            'tax_type' => 'exclusive',
            'created' => self::STORED,
            'updated' => self::STORED,
        ]);
    }

    protected function assertUpgradedLineageSurvived(Connection $connection): void
    {
        if (! $connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return;
        }

        // The migration reads the naive values in the PHP timezone of the bin/console process,
        // which shares this process's php.ini (CI pins date.timezone there). Read back in that
        // zone, the instant must still be the wall-clock value that was stored.
        self::assertSame(
            self::STORED,
            $connection->fetchOne('SELECT created AT TIME ZONE ? FROM tax_rates', [ini_get('date.timezone') ?: 'UTC']),
        );
    }
}
