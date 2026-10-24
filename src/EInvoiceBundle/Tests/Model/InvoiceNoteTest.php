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
use SolidInvoice\EInvoiceBundle\Model\InvoiceNote;

#[CoversClass(InvoiceNote::class)]
final class InvoiceNoteTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $note = new InvoiceNote('Thank you for your business', 'AAI');

        self::assertSame('Thank you for your business', $note->note);
        self::assertSame('AAI', $note->subjectCode);
    }

    public function testSubjectCodeDefaultsToNull(): void
    {
        $note = new InvoiceNote('Thank you for your business');

        self::assertNull($note->subjectCode);
    }

    public function testPropertiesAreReadonly(): void
    {
        $note = new InvoiceNote('Thank you for your business');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $note->note = 'Different note';
    }
}
