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
use SolidInvoice\EInvoiceBundle\Model\CardInformation;

#[CoversClass(CardInformation::class)]
final class CardInformationTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $card = new CardInformation('1234', 'Jane Doe');

        self::assertSame('1234', $card->primaryAccountNumber);
        self::assertSame('Jane Doe', $card->holderName);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $card = new CardInformation('1234');

        self::assertNull($card->holderName);
    }

    public function testPropertiesAreReadonly(): void
    {
        $card = new CardInformation('1234');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $card->primaryAccountNumber = '5678';
    }
}
