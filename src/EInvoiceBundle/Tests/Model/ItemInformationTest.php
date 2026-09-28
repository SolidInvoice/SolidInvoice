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
use SolidInvoice\EInvoiceBundle\Model\ItemAttribute;
use SolidInvoice\EInvoiceBundle\Model\ItemInformation;

#[CoversClass(ItemInformation::class)]
final class ItemInformationTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $item = new ItemInformation(
            name: 'Consulting services',
            description: 'One day of consulting',
            sellerIdentifier: new Identifier('SKU-1'),
            buyerIdentifier: new Identifier('PO-ITEM-1'),
            standardIdentifier: new Identifier('05012345678900', '0160'),
            classificationIdentifier: new Identifier('12345', '9', '1.0'),
            originCountryCode: 'ZA',
            attributes: [new ItemAttribute('Colour', 'Red')],
        );

        self::assertSame('Consulting services', $item->name);
        self::assertSame('One day of consulting', $item->description);
        self::assertSame('SKU-1', $item->sellerIdentifier?->value);
        self::assertSame('PO-ITEM-1', $item->buyerIdentifier?->value);
        self::assertSame('05012345678900', $item->standardIdentifier?->value);
        self::assertSame('12345', $item->classificationIdentifier?->value);
        self::assertSame('1.0', $item->classificationIdentifier->schemeVersion);
        self::assertSame('ZA', $item->originCountryCode);
        self::assertCount(1, $item->attributes);
    }

    public function testOptionalPropertiesDefaultToNullOrEmpty(): void
    {
        $item = new ItemInformation('Consulting services');

        self::assertNull($item->description);
        self::assertNull($item->sellerIdentifier);
        self::assertNull($item->buyerIdentifier);
        self::assertNull($item->standardIdentifier);
        self::assertNull($item->classificationIdentifier);
        self::assertNull($item->originCountryCode);
        self::assertSame([], $item->attributes);
    }

    public function testPropertiesAreReadonly(): void
    {
        $item = new ItemInformation('Consulting services');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $item->name = 'Other services';
    }
}
