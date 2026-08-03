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

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Component\Uid\Ulid;

#[Group('functional')]
final class InvoiceLineTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Line::class;
    }

    public function testCreate(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'description' => 'Item 1',
            'price' => 1000,
            'qty' => 2.0,
        ];

        $result = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        self::assertSame('Item 1', $result['description']);
        self::assertEquals(2.0, $result['qty']);
        self::assertArrayHasKey('total', $result);
    }

    /**
     * `qty` is a plain number on the wire, unlike `price` and `total`, which are scaled
     * into the minor unit. A fractional quantity has to come back exactly as sent.
     */
    public function testCreateWithAFractionalQuantity(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $result = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'description' => 'Metered usage',
            'price' => 1000,
            'qty' => 2.5,
        ]);

        self::assertSame(2.5, $result['qty']);
        // price is sent in the major unit, so 1000 × 2.5 comes back as 2500.
        self::assertEquals(2500, $result['total']);

        $reloaded = $this->requestGet('/api/invoices/' . $invoiceId . '/line/' . $result['id']);

        self::assertSame(2.5, $reloaded['qty']);
    }

    public function testEditAQuantityToSixDecimalPlaces(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $created = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'description' => 'Metered usage',
            'price' => 1000,
            'qty' => 1,
        ]);

        $updated = $this->requestPatch(
            '/api/invoices/' . $invoiceId . '/line/' . $created['id'],
            ['qty' => 0.123456]
        );

        self::assertSame(0.123456, $updated['qty']);
    }

    public function testGet(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'description' => 'Test Item',
            'price' => 500,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestGet('/api/invoices/' . $invoiceId . '/line/' . $lineId);

        self::assertSame('Test Item', $data['description']);
        self::assertSame($lineId, $data['id']);
        self::assertEquals(1.0, $data['qty']);
    }

    public function testEdit(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'description' => 'Original Item',
            'price' => 300,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestPatch(
            '/api/invoices/' . $invoiceId . '/line/' . $lineId,
            ['description' => 'Updated Item']
        );

        self::assertSame('Updated Item', $data['description']);
        self::assertSame($lineId, $data['id']);
    }

    public function testDelete(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'description' => 'Item To Delete',
            'price' => 100,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $this->requestDelete('/api/invoices/' . $invoiceId . '/line/' . $lineId);
    }

    public function testGetCollection(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'description' => 'Collection Item 1',
            'price' => 100,
            'qty' => 1.0,
        ]);

        $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'description' => 'Collection Item 2',
            'price' => 200,
            'qty' => 2.0,
        ]);

        $data = $this->requestGetCollection('/api/invoices/' . $invoiceId . '/lines');

        self::assertArraySubset([
            '@type' => 'Collection',
        ], $data);
    }
}
