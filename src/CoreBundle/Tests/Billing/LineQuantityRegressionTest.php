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

namespace SolidInvoice\CoreBundle\Tests\Billing;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\CoreBundle\Tests\Fixtures\LineQuantityCorpus;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use function sprintf;

/**
 * Proves that storing `qty` as an exact decimal instead of a float does not move a single
 * existing invoice total, and that the new arithmetic is exact.
 *
 * Every case runs the whole corpus in one test rather than one case per data set, so a
 * regression reports how many lines moved, not just the first.
 *
 * @see LineQuantityCorpus
 */
final class LineQuantityRegressionTest extends TestCase
{
    /**
     * The line total as it was computed before this change: an exact price multiplied by
     * the float quantity cast to a string, which serialises at PHP's `precision` setting.
     */
    private function legacyTotal(int $price, float $qty): BigDecimal
    {
        return BigDecimal::of($price)->multipliedBy(LineQuantityCorpus::legacyQtyString($qty));
    }

    /**
     * The line total after the migration: the quantity read back out of the decimal column
     * and multiplied straight into the price.
     */
    private function migratedTotal(int $price, float $qty): BigDecimal
    {
        $line = new Line();
        $line->setPrice($price);
        $line->setQty(
            new QuantityType()->convertToPHPValue(LineQuantityCorpus::migrated($qty), new MySQLPlatform())
                ?? BigDecimal::zero()
        );

        return $line->updateTotal()->getTotal()->toBigDecimal();
    }

    /**
     * The stored total is what an invoice actually carries: minor units, rounded half-even.
     */
    private function stored(BigDecimal $total): string
    {
        return (string) new BigIntegerType()->convertToDatabaseValue($total, new MySQLPlatform());
    }

    public function testEveryStoredTotalInTheCorpusIsUnchanged(): void
    {
        $moved = [];

        foreach (LineQuantityCorpus::lines() as [$price, $qty]) {
            $legacy = $this->stored($this->legacyTotal($price, $qty));
            $migrated = $this->stored($this->migratedTotal($price, $qty));

            if ($legacy !== $migrated) {
                $moved[] = sprintf('%d × %s: %s -> %s', $price, (string) $qty, $legacy, $migrated);
            }
        }

        self::assertSame([], $moved, 'Stored line totals changed for these corpus lines');
    }

    public function testEveryUnroundedTotalInTheCorpusIsUnchanged(): void
    {
        $moved = [];

        foreach (LineQuantityCorpus::lines() as [$price, $qty]) {
            $legacy = $this->legacyTotal($price, $qty);
            $migrated = $this->migratedTotal($price, $qty);

            if (! $legacy->isEqualTo($migrated)) {
                $moved[] = sprintf('%d × %s: %s -> %s', $price, (string) $qty, (string) $legacy, (string) $migrated);
            }
        }

        self::assertSame([], $moved, 'Unrounded line totals changed for these corpus lines');
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function exactProducts(): iterable
    {
        foreach (LineQuantityCorpus::prices() as $price) {
            foreach (LineQuantityCorpus::fullScaleQuantities() as $qty) {
                yield sprintf('%d x %s', $price, $qty) => [$price, $qty];
            }
        }
    }

    /**
     * `price × qty` against a reference that cannot round: an exact rational.
     */
    #[DataProvider('exactProducts')]
    public function testTheProductIsExact(int $price, string $qty): void
    {
        $line = new Line();
        $line->setPrice($price);
        $line->setQty($qty);

        $expected = BigRational::of($price)->multipliedBy(BigRational::of($qty));

        self::assertTrue(
            $expected->isEqualTo($line->updateTotal()->getTotal()),
            sprintf('Expected %s, got %s', (string) $expected->toBigDecimal(), (string) $line->getTotal())
        );
    }

    #[DataProvider('exactProducts')]
    public function testQuoteLinesUseTheSameArithmetic(int $price, string $qty): void
    {
        $invoiceLine = new Line();
        $invoiceLine->setPrice($price)->setQty($qty);

        $quoteLine = new QuoteLine();
        $quoteLine->setPrice($price)->setQty($qty);

        self::assertSame(
            (string) $invoiceLine->updateTotal()->getTotal(),
            (string) $quoteLine->updateTotal()->getTotal()
        );
    }

    /**
     * The failure this change exists to remove: a quantity with more significant digits
     * than PHP's `precision` setting emits used to be silently truncated on its way into
     * the multiplication, so the line total came out short.
     */
    public function testAQuantityWithMoreDigitsThanAFloatCanCarrySurvives(): void
    {
        // The legacy path only lost digits while `precision` emitted fewer than 15 of them,
        // which is PHP's default. State that rather than assume it: on a host configured
        // otherwise the float cast below keeps every digit and the comparison is vacuous.
        self::assertLessThan(15, (int) ini_get('precision'));

        // 15 significant digits — one more than a `precision` of 14 emits.
        $qty = '123456789.123456';

        $line = new Line();
        $line->setPrice(100)->setQty($qty);

        self::assertSame('12345678912.345600', (string) $line->updateTotal()->getTotal());

        $legacy = BigDecimal::of(100)->multipliedBy(LineQuantityCorpus::legacyQtyString((float) $qty));

        self::assertFalse(
            $line->getTotal()->isEqualTo($legacy),
            'This quantity is supposed to be one the old float path could not carry'
        );
    }

    public function testTheStoredQuantityRoundsHalfEvenAtTheScaleLimit(): void
    {
        $type = new QuantityType();
        $platform = new MySQLPlatform();

        self::assertSame('0.000002', $type->convertToDatabaseValue(BigDecimal::of('0.0000015'), $platform));
        self::assertSame('0.000000', $type->convertToDatabaseValue(BigDecimal::of('0.0000005'), $platform));
        self::assertSame('0.000002', $type->convertToDatabaseValue(BigDecimal::of('0.0000025'), $platform));
    }
}
