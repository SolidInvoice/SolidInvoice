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

namespace SolidInvoice\EInvoiceBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Exception\DocumentAlreadyClearedException;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use function hash;

#[CoversClass(EInvoiceDocument::class)]
final class EInvoiceDocumentTest extends TestCase
{
    public function testConstructorComputesPayloadHashFromPayload(): void
    {
        $document = $this->createDocument(payload: '<Invoice>1</Invoice>');

        self::assertSame(hash('sha256', '<Invoice>1</Invoice>'), $document->getPayloadHash());
    }

    public function testConstructedDocumentIsPending(): void
    {
        $document = $this->createDocument();

        self::assertSame(EInvoiceStatus::Pending, $document->getStatus());
    }

    /**
     * @return iterable<string, list<EInvoiceStatus>>
     */
    public static function terminalStatusProvider(): iterable
    {
        yield 'accepted' => [EInvoiceStatus::Accepted];
        yield 'rejected' => [EInvoiceStatus::Rejected];
        yield 'cancelled' => [EInvoiceStatus::Cancelled];
        yield 'failed' => [EInvoiceStatus::Failed];
    }

    #[DataProvider('terminalStatusProvider')]
    public function testSetPayloadThrowsWhenTerminal(EInvoiceStatus $status): void
    {
        $document = $this->createDocument();
        $document->setStatus($status);

        $this->expectException(DocumentAlreadyClearedException::class);

        $document->setPayload('<Invoice>changed</Invoice>');
    }

    #[DataProvider('terminalStatusProvider')]
    public function testSetPayloadHashThrowsWhenTerminal(EInvoiceStatus $status): void
    {
        $document = $this->createDocument();
        $document->setStatus($status);

        $this->expectException(DocumentAlreadyClearedException::class);

        $document->setPayloadHash('changed');
    }

    #[DataProvider('terminalStatusProvider')]
    public function testSetExternalIdThrowsWhenTerminal(EInvoiceStatus $status): void
    {
        $document = $this->createDocument();
        $document->setStatus($status);

        $this->expectException(DocumentAlreadyClearedException::class);

        $document->setExternalId('changed');
    }

    #[DataProvider('terminalStatusProvider')]
    public function testSetReceiptThrowsWhenTerminal(EInvoiceStatus $status): void
    {
        $document = $this->createDocument();
        $document->setStatus($status);

        $this->expectException(DocumentAlreadyClearedException::class);

        $document->setReceipt('changed');
    }

    /**
     * The ordering trap: during the `accept` transition the marking is still `transmitted` when
     * `externalId` and `receipt` are written, so that write must remain allowed. A guard checked
     * against the transition being applied, rather than the current marking, would reject this
     * and break every real transmission — `design` §6 on SOL-89.
     */
    public function testSetPayloadSucceedsWhileTransmitted(): void
    {
        $document = $this->createDocument();
        $document->setStatus(EInvoiceStatus::Transmitted);

        $document->setPayload('<Invoice>changed</Invoice>');

        self::assertSame('<Invoice>changed</Invoice>', $document->getPayload());
    }

    public function testSetPayloadHashSucceedsWhileTransmitted(): void
    {
        $document = $this->createDocument();
        $document->setStatus(EInvoiceStatus::Transmitted);

        $document->setPayloadHash('changed');

        self::assertSame('changed', $document->getPayloadHash());
    }

    public function testSetExternalIdSucceedsWhileTransmitted(): void
    {
        $document = $this->createDocument();
        $document->setStatus(EInvoiceStatus::Transmitted);

        $document->setExternalId('ext-123');

        self::assertSame('ext-123', $document->getExternalId());
    }

    public function testSetReceiptSucceedsWhileTransmitted(): void
    {
        $document = $this->createDocument();
        $document->setStatus(EInvoiceStatus::Transmitted);

        $document->setReceipt('<Receipt/>');

        self::assertSame('<Receipt/>', $document->getReceipt());
    }

    private function createDocument(string $payload = '<Invoice></Invoice>'): EInvoiceDocument
    {
        return new EInvoiceDocument(
            new Company(),
            new Invoice(),
            null,
            'INV-0001',
            'peppol',
            'peppol-bis-billing-3.0',
            $payload,
            SyntaxFormat::Ubl,
            'application/xml',
            'invoice-ubl.xml',
        );
    }
}
