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

use DoctrineMigrations\Version30100_3;
use DoctrineMigrations\Version30100_4;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function sprintf;

/**
 * `Version30100_4::postUp()` binds the `app_config.id` it inserts through {@see UlidType}
 * ({@see Version30100_4}), the type that already knows `uuid` on PostgreSQL and `binary(16)`
 * elsewhere. Binding it as a plain string instead, as a past version of this migration did,
 * lets PostgreSQL reject the raw ULID bytes outright and stops the whole migration chain from
 * reaching head. This proves the insert both succeeds and round-trips, on whichever real engine
 * {@see MigrationIsIdempotentAcrossLineagesTestCase} is running against.
 */
final class PaymentTermsSettingMigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30100_4::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30100_3::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return ['payment_terms'];
    }

    public function testPostUpWritesOneRoundTrippableUlidPerCompany(): void
    {
        $databaseName = $this->databaseName('app-config-rows');
        $this->recreateDatabase($databaseName);

        try {
            $this->console($databaseName, ['doctrine:migrations:migrate', '--no-interaction']);

            $connection = $this->connection($databaseName);

            try {
                $companyIds = $connection->fetchFirstColumn('SELECT id FROM companies');
                self::assertNotEmpty($companyIds, 'The migrated chain should have left at least one company behind.');

                $platform = $connection->getDatabasePlatform();
                $ulidType = new UlidType();

                foreach ($companyIds as $companyId) {
                    $rows = $connection->fetchAllAssociative(
                        'SELECT id FROM app_config WHERE company_id = ? AND setting_key = ?',
                        [$companyId, 'invoice/payment_terms'],
                    );

                    self::assertCount(
                        1,
                        $rows,
                        sprintf('Company %s should have exactly one invoice/payment_terms row.', $companyId),
                    );

                    self::assertInstanceOf(Ulid::class, $ulidType->convertToPHPValue($rows[0]['id'], $platform));
                }
            } finally {
                $connection->close();
            }
        } finally {
            $this->dropDatabase($databaseName);
        }
    }
}
