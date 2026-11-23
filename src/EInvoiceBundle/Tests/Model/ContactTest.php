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

#[CoversClass(Contact::class)]
final class ContactTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $contact = new Contact('Jane Doe', '+27 21 555 0100', 'jane@example.com');

        self::assertSame('Jane Doe', $contact->name);
        self::assertSame('+27 21 555 0100', $contact->telephone);
        self::assertSame('jane@example.com', $contact->email);
    }

    public function testAllPropertiesDefaultToNull(): void
    {
        $contact = new Contact();

        self::assertNull($contact->name);
        self::assertNull($contact->telephone);
        self::assertNull($contact->email);
    }

    public function testPropertiesAreReadonly(): void
    {
        $contact = new Contact();

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $contact->email = 'other@example.com';
    }
}
