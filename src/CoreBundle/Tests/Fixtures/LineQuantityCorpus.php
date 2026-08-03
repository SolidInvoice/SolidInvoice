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

namespace SolidInvoice\CoreBundle\Tests\Fixtures;

use function count;
use function mt_rand;
use function mt_srand;
use function number_format;

/**
 * A corpus of `(price, qty)` pairs standing in for the lines of already-issued invoices,
 * used to prove that moving `qty` from a float column to `DECIMAL(20, 6)` leaves every
 * existing total unchanged.
 *
 * Quantities are expressed as the *float* the old column held, because that is what a
 * pre-migration database contains. The value the migration lands in the new column is
 * whatever the database rounds that double to at the column scale — {@see self::migrated()}.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Billing\LineQuantityRegressionTest
 * @see \SolidInvoice\CoreBundle\Tests\Functional\LineQuantityPersistenceTest
 *
 * @codeCoverageIgnore
 */
final class LineQuantityCorpus
{
    /**
     * Deterministic, so a failure is always reproducible.
     */
    private const int SEED = 20250803;

    /**
     * Quantities seen on real invoices, plus the awkward cases around them: whole units,
     * halves and quarters, hours billed to two and four places, metered usage, and values
     * that a double cannot represent exactly.
     *
     * @return list<float>
     */
    public static function quantities(): array
    {
        $quantities = [
            1.0, 2.0, 3.0, 5.0, 10.0, 12.0, 24.0, 100.0, 250.0, 1000.0,
            0.5, 1.5, 2.5, 7.5, 0.25, 0.75, 3.25, 8.75,
            // Hours as decimals: 5, 10, 20 and 45 minutes, and typical retainer blocks.
            0.0833, 0.1667, 0.3333, 1.25, 7.25, 37.5, 160.0,
            // No exact double representation.
            0.1, 0.2, 0.3, 0.7, 1.1, 2.2, 4.4, 8.8, 1.005, 2.675,
            // Metered usage, including the limits of the chosen scale.
            0.001, 0.0001, 1234.5678, 999.999, 0.000001, 0.123456,
        ];

        // A deterministic sweep, so the corpus is not only the cases someone thought of.
        mt_srand(self::SEED);

        for ($i = 0; $i < 200; ++$i) {
            $quantities[] = (float) number_format(mt_rand(1, 5_000_000) / 10_000, $i % 7, '.', '');
        }

        mt_srand();

        return $quantities;
    }

    /**
     * Prices in the minor unit, spanning the range the money columns hold.
     *
     * @return list<int>
     */
    public static function prices(): array
    {
        return [1, 7, 99, 100, 333, 1000, 1999, 12345, 100000, 999999, 123456789];
    }

    /**
     * @return list<array{int, float}>
     */
    public static function lines(): array
    {
        $prices = self::prices();
        $lines = [];

        foreach (self::quantities() as $index => $qty) {
            $lines[] = [$prices[$index % count($prices)], $qty];
        }

        return $lines;
    }

    /**
     * The value the migration lands in the `DECIMAL(20, 6)` column for a float that was
     * previously stored: the database rounds the double to the column scale.
     */
    public static function migrated(float $qty): string
    {
        return number_format($qty, 6, '.', '');
    }

    /**
     * How the total was computed before this change: the float cast to a string at PHP's
     * `precision` ini setting, then multiplied into the exactly-computed price.
     */
    public static function legacyQtyString(float $qty): string
    {
        return (string) $qty;
    }

    /**
     * Quantities at the exact limit of the chosen scale, for round-trip tests.
     *
     * @return list<string>
     */
    public static function fullScaleQuantities(): array
    {
        return [
            '0.000001',
            '0.123456',
            '1.000001',
            '99.999999',
            '1234.567891',
        ];
    }
}
