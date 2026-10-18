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

namespace SolidInvoice\InvoiceBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;

#[CoversClass(InvoiceAttachment::class)]
final class InvoiceAttachmentTest extends TestCase
{
    public function testSharedWithClientDefaultsToFalse(): void
    {
        $attachment = new InvoiceAttachment(new Invoice(), 'receipt.pdf', 'application/pdf', 1024, 'storage-key');

        self::assertFalse($attachment->isSharedWithClient());
    }

    public function testConstructorSetsTheGivenValues(): void
    {
        $invoice = new Invoice();
        $attachment = new InvoiceAttachment($invoice, 'receipt.pdf', 'application/pdf', 1024, 'storage-key');

        self::assertSame($invoice, $attachment->getInvoice());
        self::assertSame('receipt.pdf', $attachment->getFilename());
        self::assertSame('application/pdf', $attachment->getMimeType());
        self::assertSame(1024, $attachment->getFileSize());
        self::assertSame('storage-key', $attachment->getStorageKey());
        self::assertNull($attachment->getUploadedBy());
    }

    public function testSetSharedWithClientIsMutable(): void
    {
        $attachment = new InvoiceAttachment(new Invoice(), 'receipt.pdf', 'application/pdf', 1024, 'storage-key');

        $attachment->setSharedWithClient(true);

        self::assertTrue($attachment->isSharedWithClient());
    }
}
