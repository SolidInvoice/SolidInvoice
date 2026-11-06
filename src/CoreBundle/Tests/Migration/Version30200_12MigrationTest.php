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

use DoctrineMigrations\Version30200_12;
use DoctrineMigrations\Version30200_8;
use function sprintf;

/**
 * {@see \SolidInvoice\PaymentBundle\Entity\PaymentInstruction} declares its uniqueness as an
 * `#[ORM\UniqueConstraint]` so a fresh install, built by {@see \Doctrine\ORM\Tools\SchemaTool}
 * from the current mapping, carries the same `uniq_payment_instructions_company_label` index as
 * an upgraded install that replayed {@see Version30200_12}. {@see MigrationIsIdempotentAcrossLineagesTestCase}
 * proves both lineages agree; `tests/bootstrap.php` builds the ordinary functional test schema
 * straight from ORM metadata, so a drift between the mapping and this migration would otherwise
 * never show up in the suite.
 */
final class Version30200_12MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30200_12::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30200_8::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return ['payment_instructions'];
    }

    public function testPostUpWritesOneRemittanceReferenceSettingPerCompany(): void
    {
        $databaseName = $this->databaseName('app-config-rows');
        $this->recreateDatabase($databaseName);

        try {
            $this->console($databaseName, ['doctrine:migrations:migrate', '--no-interaction']);

            $connection = $this->connection($databaseName);

            try {
                $companyIds = $connection->fetchFirstColumn('SELECT id FROM companies');
                self::assertNotEmpty($companyIds, 'The migrated chain should have left at least one company behind.');

                foreach ($companyIds as $companyId) {
                    $rows = $connection->fetchAllAssociative(
                        'SELECT setting_value, default_value, description FROM app_config WHERE company_id = ? AND setting_key = ?',
                        [$companyId, 'invoice/payment_instructions/remittance_reference'],
                    );

                    self::assertCount(
                        1,
                        $rows,
                        sprintf('Company %s should have exactly one invoice/payment_instructions/remittance_reference row.', $companyId),
                    );

                    // Must match what SolidInvoice\CoreBundle\Company\DefaultData writes on a
                    // fresh install for the same SolidInvoice\InvoiceBundle\Config\ConfigProvider
                    // entry, so both lineages render the same settings-form help text and store
                    // the same trial-restricted fallback value.
                    self::assertSame('{id}', $rows[0]['setting_value']);
                    self::assertSame('{id}', $rows[0]['default_value']);
                    self::assertSame('settings.description.invoice.payment_instructions.remittance_reference', $rows[0]['description']);
                }
            } finally {
                $connection->close();
            }
        } finally {
            $this->dropDatabase($databaseName);
        }
    }
}
