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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version30100_13 extends AbstractMigration
{
    private const array TABLES = ['invoices', 'recurring_invoices', 'quotes'];

    public function getDescription(): string
    {
        return 'Canonicalise percentage discounts stored in hundredths (SOL-342) to whole percent';
    }

    public function up(Schema $schema): void
    {
        // Data-only. See postUp().
    }

    /**
     * A percentage discount typed through the form before SOL-342 was stored as
     * hundredths (15 % became 1500), and `Calculator::calculatePercentage()` corrected
     * that back with `$percentage > 100 ? $percentage / 100 : $percentage` before this
     * fix removed the guess. This UPDATE applies that same correction to the stored
     * value once, so the code can use the column literally from now on.
     *
     * Today's effective percentage for every row is already `v > 100 ? v / 100 : v`.
     * This sets the column to exactly that, so the discount applied to every row is
     * unchanged before and after — no issued document's total moves.
     *
     * Rows with `0 < v <= 100` are left alone on purpose. A row storing `50` because
     * someone typed `0.5 %` through the old form is byte-identical to a legitimate
     * `50 %` written through the API, which never used the hundredths convention.
     * There is no way to tell those apart after the fact, so this migration does not
     * try to.
     *
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
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
        // Deliberate data no-op. A symmetric down() would re-multiply every row at or
        // below 100 by 100, but that band holds both migrated hundredths values and
        // legitimate whole-percent values (API-written or otherwise) with no way to
        // tell them apart — see postUp(). Re-multiplying all of them would be wrong
        // more often than it would be right.
    }
}
