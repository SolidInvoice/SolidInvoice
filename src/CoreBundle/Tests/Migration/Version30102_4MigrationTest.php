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
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use DoctrineMigrations\Version30102_3;
use DoctrineMigrations\Version30102_4;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class Version30102_4MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    private const string INVOICE_UUID = '0190a1b2-c3d4-7e5f-8a9b-0c1d2e3f4a5b';

    protected static function migrationClass(): string
    {
        return Version30102_4::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30102_3::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return [
            // Index renamed to Doctrine's generated name.
            'uniq_companies_custom_domain',
            'uniq_mcp_refresh_token_identifier',
            // Stale composite index dropped.
            'IDX_1483A5E976F5C865',
            // Index replaced with the mapping's column order.
            'UNIQ_4967254D19EB6921979B1AD6',
            // FK the mapping no longer declares.
            'FK_8E225B2E18C491A5',
        ];
    }

    // invoices.uuid is BINARY(16) only on MySQL/MariaDB; PostgreSQL stores a native uuid.
    protected function seedUpgradedLineage(Connection $connection): void
    {
        if (! $connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return;
        }

        $companyId = new Ulid()->toBinary();
        $clientId = new Ulid()->toBinary();
        $now = '2026-01-01 00:00:00';

        $connection->insert('companies', ['id' => $companyId, 'name' => 'Acme']);
        $connection->insert('clients', ['id' => $clientId, 'company_id' => $companyId, 'name' => 'Client', 'status' => 'active', 'created' => $now, 'updated' => $now]);
        $connection->insert('invoices', [
            'id' => new Ulid()->toBinary(),
            'uuid' => Uuid::fromString(self::INVOICE_UUID)->toBinary(),
            'company_id' => $companyId,
            'client_id' => $clientId,
            'invoice_id' => '1',
            'status' => 'draft',
            'total_amount' => 0,
            'baseTotal_amount' => 0,
            'balance_amount' => 0,
            'tax_amount' => 0,
            'discount_valueMoney_amount' => 0,
            'created' => $now,
            'updated' => $now,
            'invoice_date' => $now,
        ]);
    }

    protected function assertUpgradedLineageSurvived(Connection $connection): void
    {
        if (! $connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return;
        }

        self::assertSame(self::INVOICE_UUID, $connection->createQueryBuilder()->select('uuid')->from('invoices')->fetchOne());
    }
}
