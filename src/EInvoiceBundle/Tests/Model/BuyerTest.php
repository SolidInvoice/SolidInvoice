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
use SolidInvoice\EInvoiceBundle\Model\Buyer;
use SolidInvoice\EInvoiceBundle\Model\Contact;
use SolidInvoice\EInvoiceBundle\Model\Identifier;
use SolidInvoice\EInvoiceBundle\Model\PostalAddress;

#[CoversClass(Buyer::class)]
final class BuyerTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $buyer = new Buyer(
            name: 'Client Ltd',
            tradingName: 'Client',
            identifier: new Identifier('9506'),
            legalRegistrationIdentifier: new Identifier('REG456'),
            vatIdentifier: 'ZA7654321',
            electronicAddress: new Identifier('0088:0987654321', '0088'),
            address: new PostalAddress(city: 'Johannesburg'),
            contact: new Contact(name: 'John Smith'),
        );

        self::assertSame('Client Ltd', $buyer->name);
        self::assertSame('Client', $buyer->tradingName);
        self::assertSame('9506', $buyer->identifier?->value);
        self::assertSame('REG456', $buyer->legalRegistrationIdentifier?->value);
        self::assertSame('ZA7654321', $buyer->vatIdentifier);
        self::assertSame('0088:0987654321', $buyer->electronicAddress?->value);
        self::assertSame('Johannesburg', $buyer->address?->city);
        self::assertSame('John Smith', $buyer->contact?->name);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $buyer = new Buyer('Client Ltd');

        self::assertNull($buyer->tradingName);
        self::assertNull($buyer->identifier);
        self::assertNull($buyer->legalRegistrationIdentifier);
        self::assertNull($buyer->vatIdentifier);
        self::assertNull($buyer->electronicAddress);
        self::assertNull($buyer->address);
        self::assertNull($buyer->contact);
    }

    public function testPropertiesAreReadonly(): void
    {
        $buyer = new Buyer('Client Ltd');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $buyer->name = 'Other Ltd';
    }
}
