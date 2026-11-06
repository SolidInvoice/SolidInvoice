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
use SolidInvoice\EInvoiceBundle\Model\Contact;
use SolidInvoice\EInvoiceBundle\Model\Identifier;
use SolidInvoice\EInvoiceBundle\Model\PostalAddress;
use SolidInvoice\EInvoiceBundle\Model\Seller;

#[CoversClass(Seller::class)]
final class SellerTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $seller = new Seller(
            name: 'ACME Corp',
            tradingName: 'ACME',
            identifiers: [new Identifier('9506')],
            legalRegistrationIdentifier: new Identifier('REG123'),
            vatIdentifier: 'ZA1234567',
            taxRegistrationIdentifier: 'TAX123',
            additionalLegalInformation: 'Registered in the Western Cape',
            electronicAddress: new Identifier('0088:1234567890', '0088'),
            address: new PostalAddress(city: 'Cape Town'),
            contact: new Contact(name: 'Jane Doe'),
        );

        self::assertSame('ACME Corp', $seller->name);
        self::assertSame('ACME', $seller->tradingName);
        self::assertCount(1, $seller->identifiers);
        self::assertSame('REG123', $seller->legalRegistrationIdentifier?->value);
        self::assertSame('ZA1234567', $seller->vatIdentifier);
        self::assertSame('TAX123', $seller->taxRegistrationIdentifier);
        self::assertSame('Registered in the Western Cape', $seller->additionalLegalInformation);
        self::assertSame('0088:1234567890', $seller->electronicAddress?->value);
        self::assertSame('Cape Town', $seller->address?->city);
        self::assertSame('Jane Doe', $seller->contact?->name);
    }

    public function testOptionalPropertiesDefaultToNullOrEmpty(): void
    {
        $seller = new Seller('ACME Corp');

        self::assertNull($seller->tradingName);
        self::assertSame([], $seller->identifiers);
        self::assertNull($seller->legalRegistrationIdentifier);
        self::assertNull($seller->vatIdentifier);
        self::assertNull($seller->taxRegistrationIdentifier);
        self::assertNull($seller->additionalLegalInformation);
        self::assertNull($seller->electronicAddress);
        self::assertNull($seller->address);
        self::assertNull($seller->contact);
    }

    public function testPropertiesAreReadonly(): void
    {
        $seller = new Seller('ACME Corp');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $seller->name = 'Other Corp';
    }
}
