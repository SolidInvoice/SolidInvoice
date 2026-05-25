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

namespace SolidInvoice\TaxBundle\Tests\Service;

use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Service\TaxSnapshotCopier;

final class TaxSnapshotCopierTest extends TestCase
{
    public function testCopyLineTaxPreservesSnapshotFieldsVerbatim(): void
    {
        $source = new LineTax();
        $source->setNameSnapshot('GST');
        $source->setRateSnapshot('5.0000');
        $source->setCategorySnapshot(TaxCategory::Standard);
        $source->setTypeSnapshot(TaxType::Exclusive);
        $source->setCompound(false);
        $source->setSequence(1);
        $source->setAmount(BigInteger::of('500'));

        $copy = new TaxSnapshotCopier()->copyLineTax($source);

        self::assertNotSame($source, $copy);
        self::assertSame('GST', $copy->getNameSnapshot());
        self::assertSame('5.0000', $copy->getRateSnapshot());
        self::assertSame(TaxCategory::Standard, $copy->getCategorySnapshot());
        self::assertSame(TaxType::Exclusive, $copy->getTypeSnapshot());
        self::assertFalse($copy->isCompound());
        self::assertSame(1, $copy->getSequence());
        self::assertSame('500', (string) $copy->getAmount());
        self::assertNull($copy->getSnapshottedAt());
    }

    public function testCopyLineTaxFreezesWhenFreezeAtProvided(): void
    {
        $source = new LineTax();
        $source->setNameSnapshot('VAT');
        $source->setRateSnapshot('20.0000');

        $stamp = CarbonImmutable::parse('2026-05-13 10:00:00');
        $copy = new TaxSnapshotCopier()->copyLineTax($source, $stamp);

        self::assertSame($stamp, $copy->getSnapshottedAt());
    }

    public function testCopyLineTaxPreservesExistingSnapshottedAt(): void
    {
        $source = new LineTax();
        $source->setNameSnapshot('VAT');
        $source->setRateSnapshot('20.0000');

        $stamp = CarbonImmutable::parse('2025-01-01 00:00:00');
        $source->freeze($stamp);

        $copy = new TaxSnapshotCopier()->copyLineTax($source);

        self::assertSame($stamp, $copy->getSnapshottedAt());
    }

    public function testCopyInvoiceTaxPreservesSnapshotFieldsVerbatim(): void
    {
        $source = new InvoiceTax();
        $source->setNameSnapshot('TDS');
        $source->setRateSnapshot('10.0000');
        $source->setCategorySnapshot(TaxCategory::Standard);
        $source->setDirection(TaxDirection::Deductive);
        $source->setNote('Withholding 10%');
        $source->setSequence(0);
        $source->setAmount(BigInteger::of('-1000'));

        $copy = new TaxSnapshotCopier()->copyInvoiceTax($source);

        self::assertNotSame($source, $copy);
        self::assertSame('TDS', $copy->getNameSnapshot());
        self::assertSame('10.0000', $copy->getRateSnapshot());
        self::assertSame(TaxDirection::Deductive, $copy->getDirection());
        self::assertSame('Withholding 10%', $copy->getNote());
        self::assertSame(0, $copy->getSequence());
        self::assertSame('-1000', (string) $copy->getAmount());
    }
}
