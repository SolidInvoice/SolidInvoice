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
use SolidInvoice\EInvoiceBundle\Model\AdditionalSupportingDocument;

#[CoversClass(AdditionalSupportingDocument::class)]
final class AdditionalSupportingDocumentTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $document = new AdditionalSupportingDocument(
            reference: 'DOC-1',
            description: 'Timesheet',
            externalUri: 'https://example.com/doc-1',
            attachedDocument: base64_encode('content'),
            mimeCode: 'application/pdf',
            filename: 'timesheet.pdf',
        );

        self::assertSame('DOC-1', $document->reference);
        self::assertSame('Timesheet', $document->description);
        self::assertSame('https://example.com/doc-1', $document->externalUri);
        self::assertSame(base64_encode('content'), $document->attachedDocument);
        self::assertSame('application/pdf', $document->mimeCode);
        self::assertSame('timesheet.pdf', $document->filename);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $document = new AdditionalSupportingDocument('DOC-1');

        self::assertNull($document->description);
        self::assertNull($document->externalUri);
        self::assertNull($document->attachedDocument);
        self::assertNull($document->mimeCode);
        self::assertNull($document->filename);
    }

    public function testPropertiesAreReadonly(): void
    {
        $document = new AdditionalSupportingDocument('DOC-1');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $document->reference = 'DOC-2';
    }
}
