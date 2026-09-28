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
use SolidInvoice\EInvoiceBundle\Model\SellerTaxRepresentative;

#[CoversClass(SellerTaxRepresentative::class)]
final class SellerTaxRepresentativeTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $representative = new SellerTaxRepresentative(
            name: 'EU Tax Agent BV',
            vatIdentifier: 'NL123456789B01',
            address: new PostalAddress(city: 'Amsterdam'),
        );

        self::assertSame('EU Tax Agent BV', $representative->name);
        self::assertSame('NL123456789B01', $representative->vatIdentifier);
        self::assertSame('Amsterdam', $representative->address?->city);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $representative = new SellerTaxRepresentative('EU Tax Agent BV');

        self::assertNull($representative->vatIdentifier);
        self::assertNull($representative->address);
    }

    public function testPropertiesAreReadonly(): void
    {
        $representative = new SellerTaxRepresentative('EU Tax Agent BV');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $representative->name = 'Other Agent BV';
    }
}
