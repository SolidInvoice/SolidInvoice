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
use SolidInvoice\EInvoiceBundle\Model\DirectDebit;

#[CoversClass(DirectDebit::class)]
final class DirectDebitTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $directDebit = new DirectDebit(
            mandateReference: 'MANDATE-1',
            creditorIdentifier: 'ZA98ZZZ123456789',
            debitedAccountIdentifier: 'GB33BUKB20201555555555',
        );

        self::assertSame('MANDATE-1', $directDebit->mandateReference);
        self::assertSame('ZA98ZZZ123456789', $directDebit->creditorIdentifier);
        self::assertSame('GB33BUKB20201555555555', $directDebit->debitedAccountIdentifier);
    }

    public function testAllPropertiesDefaultToNull(): void
    {
        $directDebit = new DirectDebit();

        self::assertNull($directDebit->mandateReference);
        self::assertNull($directDebit->creditorIdentifier);
        self::assertNull($directDebit->debitedAccountIdentifier);
    }

    public function testPropertiesAreReadonly(): void
    {
        $directDebit = new DirectDebit();

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $directDebit->mandateReference = 'MANDATE-2';
    }
}
