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

#[CoversClass(Identifier::class)]
final class IdentifierTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $identifier = new Identifier('9506', '0088', '1.0');

        self::assertSame('9506', $identifier->value);
        self::assertSame('0088', $identifier->schemeIdentifier);
        self::assertSame('1.0', $identifier->schemeVersion);
    }

    public function testSchemeSubTermsDefaultToNull(): void
    {
        $identifier = new Identifier('9506');

        self::assertNull($identifier->schemeIdentifier);
        self::assertNull($identifier->schemeVersion);
    }

    public function testPropertiesAreReadonly(): void
    {
        $identifier = new Identifier('9506');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $identifier->value = '9507';
    }
}
