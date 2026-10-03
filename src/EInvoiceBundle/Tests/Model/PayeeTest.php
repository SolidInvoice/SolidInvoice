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
use SolidInvoice\EInvoiceBundle\Model\Identifier;
use SolidInvoice\EInvoiceBundle\Model\Payee;

#[CoversClass(Payee::class)]
final class PayeeTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $payee = new Payee(
            name: 'Factor Finance Ltd',
            identifier: new Identifier('9506'),
            legalRegistrationIdentifier: new Identifier('REG789'),
        );

        self::assertSame('Factor Finance Ltd', $payee->name);
        self::assertSame('9506', $payee->identifier?->value);
        self::assertSame('REG789', $payee->legalRegistrationIdentifier?->value);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $payee = new Payee('Factor Finance Ltd');

        self::assertNull($payee->identifier);
        self::assertNull($payee->legalRegistrationIdentifier);
    }

    public function testPropertiesAreReadonly(): void
    {
        $payee = new Payee('Factor Finance Ltd');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $payee->name = 'Other Finance Ltd';
    }
}
