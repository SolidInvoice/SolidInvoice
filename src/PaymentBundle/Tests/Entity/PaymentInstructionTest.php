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

namespace SolidInvoice\PaymentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\PaymentBundle\Entity\PaymentInstruction;
use SolidInvoice\PaymentBundle\Enum\PaymentMeans;

#[CoversClass(PaymentInstruction::class)]
final class PaymentInstructionTest extends TestCase
{
    public function testGetIdReturnsNullForNewEntity(): void
    {
        $instruction = new PaymentInstruction();

        self::assertNull($instruction->getId());
    }

    public function testSetAndGetLabel(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setLabel('Business account');

        self::assertSame('Business account', $instruction->getLabel());
    }

    public function testMeansDefaultsToBankTransfer(): void
    {
        $instruction = new PaymentInstruction();

        self::assertSame(PaymentMeans::BankTransfer, $instruction->getMeans());
    }

    public function testSetAndGetMeans(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setMeans(PaymentMeans::SepaDirectDebit);

        self::assertSame(PaymentMeans::SepaDirectDebit, $instruction->getMeans());
    }

    public function testSetAndGetAccountName(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setAccountName('Jane Freelancer');

        self::assertSame('Jane Freelancer', $instruction->getAccountName());
    }

    public function testSetAndGetAccountIdentifier(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setAccountIdentifier('GB29NWBK60161331926819');

        self::assertSame('GB29NWBK60161331926819', $instruction->getAccountIdentifier());
    }

    public function testSetAndGetServiceProviderIdentifier(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setServiceProviderIdentifier('NWBKGB2L');

        self::assertSame('NWBKGB2L', $instruction->getServiceProviderIdentifier());
    }

    public function testSetAndGetBankName(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setBankName('NatWest');

        self::assertSame('NatWest', $instruction->getBankName());
    }

    public function testSetAndGetMandateReference(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setMandateReference('MANDATE-1');

        self::assertSame('MANDATE-1', $instruction->getMandateReference());
    }

    public function testSetAndGetCreditorIdentifier(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setCreditorIdentifier('GB98ZZZ123456789012');

        self::assertSame('GB98ZZZ123456789012', $instruction->getCreditorIdentifier());
    }

    public function testSetAndGetInstructions(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setInstructions('Pay within 14 days.');

        self::assertSame('Pay within 14 days.', $instruction->getInstructions());
    }

    public function testDefaultFlagDefaultsToFalse(): void
    {
        $instruction = new PaymentInstruction();

        self::assertFalse($instruction->isDefault());
    }

    public function testSetAndGetDefaultFlag(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setDefault(true);

        self::assertTrue($instruction->isDefault());
    }

    public function testToString(): void
    {
        $instruction = new PaymentInstruction();
        $instruction->setLabel('Business account');
        $instruction->setMeans(PaymentMeans::SepaCreditTransfer);

        self::assertSame('Business account: sepa_credit_transfer', (string) $instruction);
    }
}
