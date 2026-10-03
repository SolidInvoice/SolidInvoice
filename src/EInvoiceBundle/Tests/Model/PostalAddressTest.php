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
use SolidInvoice\EInvoiceBundle\Model\PostalAddress;

#[CoversClass(PostalAddress::class)]
final class PostalAddressTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $address = new PostalAddress(
            addressLine1: '1 Example Street',
            addressLine2: 'Suite 2',
            addressLine3: 'Building 3',
            city: 'Cape Town',
            postCode: '8001',
            countrySubdivision: 'Western Cape',
            countryCode: 'ZA',
        );

        self::assertSame('1 Example Street', $address->addressLine1);
        self::assertSame('Suite 2', $address->addressLine2);
        self::assertSame('Building 3', $address->addressLine3);
        self::assertSame('Cape Town', $address->city);
        self::assertSame('8001', $address->postCode);
        self::assertSame('Western Cape', $address->countrySubdivision);
        self::assertSame('ZA', $address->countryCode);
    }

    public function testAllPropertiesDefaultToNull(): void
    {
        $address = new PostalAddress();

        self::assertNull($address->addressLine1);
        self::assertNull($address->addressLine2);
        self::assertNull($address->addressLine3);
        self::assertNull($address->city);
        self::assertNull($address->postCode);
        self::assertNull($address->countrySubdivision);
        self::assertNull($address->countryCode);
    }

    public function testPropertiesAreReadonly(): void
    {
        $address = new PostalAddress();

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $address->city = 'Johannesburg';
    }
}
