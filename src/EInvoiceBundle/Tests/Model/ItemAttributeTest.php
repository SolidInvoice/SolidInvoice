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
use SolidInvoice\EInvoiceBundle\Model\ItemAttribute;

#[CoversClass(ItemAttribute::class)]
final class ItemAttributeTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $attribute = new ItemAttribute('Colour', 'Red');

        self::assertSame('Colour', $attribute->name);
        self::assertSame('Red', $attribute->value);
    }

    public function testPropertiesAreReadonly(): void
    {
        $attribute = new ItemAttribute('Colour', 'Red');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $attribute->value = 'Blue';
    }
}
