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

namespace DoctrineMigrations;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Billing\BillingDocumentKind;
use SolidInvoice\CoreBundle\Entity\AmbiguousDiscountUnit;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30100_13 extends AbstractMigration
{
    /**
     * @var array<string, BillingDocumentKind>
     */
    private const array TABLES = [
        'invoices' => BillingDocumentKind::Invoice,
        'quotes' => BillingDocumentKind::Quote,
        'recurring_invoices' => BillingDocumentKind::RecurringInvoice,
    ];

    public function getDescription(): string
    {
        return 'Store a percentage discount as whole percent (SOL-342), capturing the rows the fix cannot repair (SOL-348) before it does';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. This was defensive while the
        // migration was data-only; it is load-bearing now that up() creates a table.
        // AbstractMySQLPlatform, because MariaDBPlatform is a sibling of MySQLPlatform
        // rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * The only DDL: {@see AmbiguousDiscountUnit}'s table, created before postUp() fills
     * it. No existing column is added, dropped, retyped, re-nullabled or indexed.
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable(AmbiguousDiscountUnit::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(AmbiguousDiscountUnit::TABLE_NAME);
        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME, ['notnull' => true]);
        $table->addColumn('document_type', Types::STRING, ['length' => 20, 'notnull' => true]);
        $table->addColumn('captured_percentage', Types::FLOAT, ['notnull' => true]);
        $table->addColumn('captured_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['company_id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    /**
     * A percentage discount typed through the form before SOL-342 was stored as
     * hundredths (15 % became 1500), and `Calculator::calculatePercentage()` corrected
     * that back with `$percentage > 100 ? $percentage / 100 : $percentage` before this
     * fix removed the guess. This canonicalises the stored value once, so the code can
     * use the column literally from now on.
     *
     * Today's effective percentage for every row is already `v > 100 ? v / 100 : v`.
     * This sets the column to exactly that, so the discount applied to every row is
     * unchanged before and after — no issued document's total moves.
     *
     * Rows with `0 < v <= 100` are left alone on purpose. A row storing `50` because
     * someone typed `0.5 %` through the old form is byte-identical to a legitimate
     * `50 %` written through the API, which never used the hundredths convention.
     * There is no way to tell those apart from the value alone — which is exactly why
     * step 1 below exists.
     *
     * 1. CAPTURE FIRST (SOL-348). Once step 2 runs, the stored value equals the applied
     *    percent for every row — the property that makes the canonicalisation
     *    total-preserving is also what erases the only evidence of which `0 < v <= 100`
     *    rows came from the hundredths convention. These two statements look
     *    independent; they are not. Reordering them loses that population for good,
     *    because the next free migration number (`_14`) sorts after this one.
     * 2. Then canonicalise, exactly as before.
     *
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $capturedAt = CarbonImmutable::now();

        foreach (self::TABLES as $table => $kind) {
            if (! $schema->getTable($table)->hasColumn('discount_value_percentage')) {
                continue;
            }

            $this->connection->executeStatement(
                sprintf(
                    'INSERT INTO %s (id, company_id, document_type, captured_percentage, captured_at) '
                    . 'SELECT id, company_id, :kind, discount_value_percentage, :capturedAt FROM %s '
                    . "WHERE discount_type = 'percentage' AND discount_value_percentage > 0 AND discount_value_percentage <= 100",
                    AmbiguousDiscountUnit::TABLE_NAME,
                    $table,
                ),
                ['kind' => $kind->value, 'capturedAt' => $capturedAt],
                ['kind' => Types::STRING, 'capturedAt' => Types::DATETIME_IMMUTABLE],
            );

            $this->connection->createQueryBuilder()
                ->update($table)
                ->set('discount_value_percentage', 'discount_value_percentage / 100')
                ->where('discount_type = :type')
                ->andWhere('discount_value_percentage > 100')
                ->setParameter('type', 'percentage')
                ->executeStatement();
        }
    }

    public function down(Schema $schema): void
    {
        // Drops the capture table. The canonicalisation itself stays a deliberate data
        // no-op: a symmetric reverse would re-multiply every row at or below 100 by
        // 100, but that band holds both migrated hundredths values and legitimate
        // whole-percent values (API-written or otherwise) with no way to tell them
        // apart — see postUp().
        $schema->dropTable(AmbiguousDiscountUnit::TABLE_NAME);
    }
}
