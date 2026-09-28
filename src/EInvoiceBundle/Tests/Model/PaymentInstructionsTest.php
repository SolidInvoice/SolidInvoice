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

namespace SolidInvoice\EInvoiceBundle\Tests\Model;

use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\PaymentMeansCode;
use SolidInvoice\EInvoiceBundle\Model\CardInformation;
use SolidInvoice\EInvoiceBundle\Model\CreditTransfer;
use SolidInvoice\EInvoiceBundle\Model\DirectDebit;
use SolidInvoice\EInvoiceBundle\Model\PaymentInstructions;

#[CoversClass(PaymentInstructions::class)]
final class PaymentInstructionsTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $instructions = new PaymentInstructions(
            paymentMeansCode: PaymentMeansCode::SepaCreditTransfer,
            paymentMeansText: 'SEPA transfer',
            remittanceInformation: 'INV-0001',
            creditTransfers: [new CreditTransfer('IBAN123')],
            card: new CardInformation('1234'),
            directDebit: new DirectDebit(mandateReference: 'MANDATE-1'),
        );

        self::assertSame(PaymentMeansCode::SepaCreditTransfer, $instructions->paymentMeansCode);
        self::assertSame('SEPA transfer', $instructions->paymentMeansText);
        self::assertSame('INV-0001', $instructions->remittanceInformation);
        self::assertCount(1, $instructions->creditTransfers);
        self::assertSame('1234', $instructions->card?->primaryAccountNumber);
        self::assertSame('MANDATE-1', $instructions->directDebit?->mandateReference);
    }

    public function testOptionalPropertiesDefaultToNullOrEmpty(): void
    {
        $instructions = new PaymentInstructions(PaymentMeansCode::CreditTransfer);

        self::assertNull($instructions->paymentMeansText);
        self::assertNull($instructions->remittanceInformation);
        self::assertSame([], $instructions->creditTransfers);
        self::assertNull($instructions->card);
        self::assertNull($instructions->directDebit);
    }

    public function testPropertiesAreReadonly(): void
    {
        $instructions = new PaymentInstructions(PaymentMeansCode::CreditTransfer);

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $instructions->paymentMeansCode = PaymentMeansCode::BankCard;
    }
}
