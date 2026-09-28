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
use SolidInvoice\EInvoiceBundle\Enum\PaymentMeansCode;

#[CoversClass(PaymentMeansCode::class)]
final class PaymentMeansCodeTest extends TestCase
{
    public function testCasesCoverUntdid4461Subset(): void
    {
        $values = array_map(static fn (PaymentMeansCode $case): string => $case->value, PaymentMeansCode::cases());

        self::assertSame(['30', '48', '57', '58', '59', '97'], $values);
    }

    /**
     * @return iterable<string, array{PaymentMeansCode, string}>
     */
    public static function labelProvider(): iterable
    {
        yield 'credit transfer' => [PaymentMeansCode::CreditTransfer, 'Credit transfer'];
        yield 'bank card' => [PaymentMeansCode::BankCard, 'Bank card'];
        yield 'standing agreement' => [PaymentMeansCode::StandingAgreement, 'Standing agreement'];
        yield 'sepa credit transfer' => [PaymentMeansCode::SepaCreditTransfer, 'SEPA credit transfer'];
        yield 'sepa direct debit' => [PaymentMeansCode::SepaDirectDebit, 'SEPA direct debit'];
        yield 'report' => [PaymentMeansCode::Report, 'Report'];
    }

    #[DataProvider('labelProvider')]
    public function testGetLabel(PaymentMeansCode $case, string $expected): void
    {
        self::assertSame($expected, $case->getLabel());
    }
}
