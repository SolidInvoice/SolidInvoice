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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use function array_column;
use function array_map;
use function count;

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
            'name' => 'Item 1',
            'price' => 1000,
            'qty' => 2.0,
        ];

        $result = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        self::assertSame('Item 1', $result['name']);
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
            'name' => 'Metered usage',
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
            'name' => 'Metered usage',
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
            'name' => 'Test Item',
            'price' => 500,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestGet('/api/invoices/' . $invoiceId . '/line/' . $lineId);

        self::assertSame('Test Item', $data['name']);
        self::assertSame($lineId, $data['id']);
        self::assertEquals(1.0, $data['qty']);
    }

    public function testEdit(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'name' => 'Original Item',
            'price' => 300,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestPatch(
            '/api/invoices/' . $invoiceId . '/line/' . $lineId,
            ['name' => 'Updated Item']
        );

        self::assertSame('Updated Item', $data['name']);
        self::assertSame($lineId, $data['id']);
    }

    public function testDelete(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'name' => 'Item To Delete',
            'price' => 100,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $this->requestDelete('/api/invoices/' . $invoiceId . '/line/' . $lineId);
    }

    /**
     * A position is the index the invoice gave the line, not a value the caller carries, so a
     * `position` in the payload is ignored. Were it not, two lines could claim the same slot
     * and the order of the collection would stop being a fact about it.
     */
    public function testPositionIsReadOnly(): void
    {
        ['id' => $invoiceId, 'lines' => $lines] = $this->createInvoiceWithLines('First', 'Second');

        self::assertSame([0, 1], array_column($lines, 'position'));

        $patched = $this->requestPatch(
            '/api/invoices/' . $invoiceId . '/line/' . $lines[1]['id'],
            ['position' => 0]
        );

        self::assertSame(1, $patched['position']);
    }

    /**
     * Deleting a line out of the middle would otherwise leave the ones after it numbered
     * around the hole — `0, 2` for what the reader sees as two lines.
     */
    public function testDeletingALineRenumbersTheOnesAfterIt(): void
    {
        ['id' => $invoiceId, 'lines' => $lines] = $this->createInvoiceWithLines('First', 'Second', 'Third');

        $this->requestDelete('/api/invoices/' . $invoiceId . '/line/' . $lines[1]['id']);

        self::assertSame(0, $this->requestGet('/api/invoices/' . $invoiceId . '/line/' . $lines[0]['id'])['position']);
        self::assertSame(1, $this->requestGet('/api/invoices/' . $invoiceId . '/line/' . $lines[2]['id'])['position']);
    }

    /**
     * Through the invoice rather than a line at a time, so the fixture does not depend on
     * what `POST /invoices/{id}/lines` does with a line that is already there.
     *
     * @return array{id: string, lines: list<array<string, mixed>>}
     */
    private function createInvoiceWithLines(string ...$descriptions): array
    {
        $client = ClientFactory::createOne();

        $invoice = $this->requestPostExpecting(
            '/api/invoices',
            [
                'client' => $this->getIriFromResource($client),
                'users' => [$this->getIriFromResource(ContactFactory::createOne(['client' => $client]))],
                'lines' => array_map(
                    static fn (string $description): array => [
                        'description' => $description,
                        'price' => 100,
                        'qty' => 1,
                        // The same out-of-range slot for every line: if it were honoured
                        // they would all share it, rather than coming back as 0..n-1.
                        'position' => count($descriptions),
                    ],
                    $descriptions,
                ),
            ],
            Invoice::class,
        );

        return ['id' => $invoice['id'], 'lines' => $invoice['lines']];
    }

    public function testGetCollection(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'name' => 'Collection Item 1',
            'price' => 100,
            'qty' => 1.0,
        ]);

        $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'name' => 'Collection Item 2',
            'price' => 200,
            'qty' => 2.0,
        ]);

        $data = $this->requestGetCollection('/api/invoices/' . $invoiceId . '/lines');

        self::assertArraySubset([
            '@type' => 'Collection',
        ], $data);

        // Both posts have to survive. Asserting only the collection's type let the second
        // post silently overwrite the first, because the response looked the same either way.
        self::assertSame(2, $data['totalItems']);
        // Canonicalizing: the lines association carries no OrderBy, so collection order
        // is unspecified. What matters here is that neither post replaced the other.
        self::assertEqualsCanonicalizing(
            ['Collection Item 1', 'Collection Item 2'],
            array_column($data['member'], 'description')
        );
    }

    /**
     * The post operation does not read an existing line first, so refusing a line whose
     * invoice is not there is the processor's job rather than the framework's.
     */
    public function testCreateOnAMissingOwnerIs404(): void
    {
        $missingId = new Ulid()->toString();

        self::$client->request(
            method: Request::METHOD_POST,
            url: '/api/invoices/' . $missingId . '/lines',
            options: [
                'json' => ['description' => 'Orphan', 'price' => 100, 'qty' => 1.0],
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * A client written before lines had a name sends only a description. It has to keep
     * working, and the line it creates has to end up with a name all the same.
     */
    public function testCreateWithOnlyADescriptionDerivesTheName(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $result = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'description' => "Website design\nIncluding two rounds of revisions.",
            'price' => 1000,
            'qty' => 1,
        ]);

        self::assertSame('Website design', $result['name']);
        self::assertSame("Website design\nIncluding two rounds of revisions.", $result['description']);
    }

    /**
     * A one-line description becomes the name in full, and is still stored. Not printing
     * the same text twice is the `line_description` macro's job; the API keeps what it
     * was sent.
     */
    public function testASingleLineDescriptionBecomesTheNameAndIsKept(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $result = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'description' => 'Website design',
            'price' => 1000,
            'qty' => 1,
        ]);

        self::assertSame('Website design', $result['name']);
        self::assertSame('Website design', $result['description']);
    }

    /**
     * The serializer calls setters in payload key order, so a client that happens to
     * serialise `description` first must not get a different line from one that does not.
     */
    public function testBothFieldsSurviveEitherKeyOrder(): void
    {
        $invoice = InvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $descriptionFirst = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'description' => 'Consulting',
            'name' => 'Widget',
            'price' => 1000,
            'qty' => 1,
        ]);

        $nameFirst = $this->requestPost('/api/invoices/' . $invoiceId . '/lines', [
            'name' => 'Widget',
            'description' => 'Consulting',
            'price' => 1000,
            'qty' => 1,
        ]);

        self::assertSame('Widget', $descriptionFirst['name']);
        self::assertSame('Consulting', $descriptionFirst['description']);
        self::assertSame($nameFirst['name'], $descriptionFirst['name']);
        self::assertSame($nameFirst['description'], $descriptionFirst['description']);
    }
}
