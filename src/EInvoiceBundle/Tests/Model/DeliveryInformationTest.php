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

use Carbon\CarbonImmutable;
use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Model\DeliveryInformation;
use SolidInvoice\EInvoiceBundle\Model\Identifier;
use SolidInvoice\EInvoiceBundle\Model\InvoicingPeriod;
use SolidInvoice\EInvoiceBundle\Model\PostalAddress;

#[CoversClass(DeliveryInformation::class)]
final class DeliveryInformationTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $delivery = new DeliveryInformation(
            deliverToName: 'Warehouse 3',
            locationIdentifier: new Identifier('LOC-3'),
            actualDeliveryDate: CarbonImmutable::parse('2026-01-15'),
            invoicingPeriod: new InvoicingPeriod(startDate: CarbonImmutable::parse('2026-01-01')),
            address: new PostalAddress(city: 'Durban'),
        );

        self::assertSame('Warehouse 3', $delivery->deliverToName);
        self::assertSame('LOC-3', $delivery->locationIdentifier?->value);
        self::assertSame('2026-01-15', $delivery->actualDeliveryDate?->format('Y-m-d'));
        self::assertSame('2026-01-01', $delivery->invoicingPeriod?->startDate?->format('Y-m-d'));
        self::assertSame('Durban', $delivery->address?->city);
    }

    public function testAllPropertiesDefaultToNull(): void
    {
        $delivery = new DeliveryInformation();

        self::assertNull($delivery->deliverToName);
        self::assertNull($delivery->locationIdentifier);
        self::assertNull($delivery->actualDeliveryDate);
        self::assertNull($delivery->invoicingPeriod);
        self::assertNull($delivery->address);
    }

    public function testPropertiesAreReadonly(): void
    {
        $delivery = new DeliveryInformation();

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $delivery->deliverToName = 'Warehouse 4';
    }
}
