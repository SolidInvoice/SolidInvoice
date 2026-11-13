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

namespace SolidInvoice\EInvoiceBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;

#[CoversClass(InvoiceTypeCode::class)]
final class InvoiceTypeCodeTest extends TestCase
{
    public function testCasesCoverUntdid1001Subset(): void
    {
        $values = array_map(static fn (InvoiceTypeCode $case): string => $case->value, InvoiceTypeCode::cases());

        self::assertSame(['380', '381', '384', '386', '389'], $values);
    }

    /**
     * @return iterable<string, array{InvoiceTypeCode, string}>
     */
    public static function labelProvider(): iterable
    {
        yield 'commercial invoice' => [InvoiceTypeCode::CommercialInvoice, 'Commercial invoice'];
        yield 'credit note' => [InvoiceTypeCode::CreditNote, 'Credit note'];
        yield 'corrected invoice' => [InvoiceTypeCode::CorrectedInvoice, 'Corrected invoice'];
        yield 'prepayment invoice' => [InvoiceTypeCode::PrepaymentInvoice, 'Prepayment invoice'];
        yield 'self-billed invoice' => [InvoiceTypeCode::SelfBilledInvoice, 'Self-billed invoice'];
    }

    #[DataProvider('labelProvider')]
    public function testGetLabel(InvoiceTypeCode $case, string $expected): void
    {
        self::assertSame($expected, $case->getLabel());
    }
}
