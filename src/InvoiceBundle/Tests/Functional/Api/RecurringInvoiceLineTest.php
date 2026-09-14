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

use DateTimeInterface;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use function array_column;
use function array_map;
use function count;
use function date;

#[Group('functional')]
final class RecurringInvoiceLineTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return RecurringInvoiceLine::class;
    }

    public function testCreate(): void
    {
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'name' => 'Item 1',
            'price' => 1000,
            'qty' => 2.0,
        ];

        $result = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', $lineData);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        self::assertSame('Item 1', $result['name']);
        self::assertEquals(2.0, $result['qty']);
        self::assertArrayHasKey('total', $result);
    }

    public function testGet(): void
    {
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'name' => 'Test Item',
            'price' => 500,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestGet('/api/recurring-invoices/' . $invoiceId . '/line/' . $lineId);

        self::assertSame('Test Item', $data['name']);
        self::assertSame($lineId, $data['id']);
        self::assertEquals(1.0, $data['qty']);
    }

    public function testEdit(): void
    {
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'name' => 'Original Item',
            'price' => 300,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestPatch(
            '/api/recurring-invoices/' . $invoiceId . '/line/' . $lineId,
            ['name' => 'Updated Item']
        );

        self::assertSame('Updated Item', $data['name']);
        self::assertSame($lineId, $data['id']);
    }

    public function testDelete(): void
    {
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $lineData = [
            'name' => 'Item To Delete',
            'price' => 100,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', $lineData);
        $lineId = $created['id'];

        $this->requestDelete('/api/recurring-invoices/' . $invoiceId . '/line/' . $lineId);
    }

    public function testGetCollection(): void
    {
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', [
            'name' => 'Collection Item 1',
            'price' => 100,
            'qty' => 1.0,
        ]);

        $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', [
            'name' => 'Collection Item 2',
            'price' => 200,
            'qty' => 2.0,
        ]);

        $data = $this->requestGetCollection('/api/recurring-invoices/' . $invoiceId . '/lines');

        self::assertArraySubset([
            '@type' => 'Collection',
        ], $data);

        // Both posts have to survive. Asserting only the collection's type let the second
        // post silently overwrite the first, because the response looked the same either way.
        self::assertSame(2, $data['totalItems']);
        // In the posted order, not canonicalized: the lines association carries an OrderBy
        // on `position` now, so a posted line lands after the ones already there and the
        // collection comes back in a specified order rather than whatever the database
        // happened to return. Asserted on `name`, which is what these posts set.
        self::assertSame(
            ['Collection Item 1', 'Collection Item 2'],
            array_column($data['member'], 'name')
        );
    }

    /**
     * The post operation does not read an existing line first, so refusing a line whose
     * recurring invoice is not there is the processor's job rather than the framework's.
     */
    public function testCreateOnAMissingOwnerIs404(): void
    {
        $missingId = new Ulid()->toString();

        self::$client->request(
            method: Request::METHOD_POST,
            url: '/api/recurring-invoices/' . $missingId . '/lines',
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
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $result = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', [
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
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $result = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', [
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
        $invoice = RecurringInvoiceFactory::createOne();
        $invoiceId = $invoice->getId()
            ->toString();

        $descriptionFirst = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', [
            'description' => 'Consulting',
            'name' => 'Widget',
            'price' => 1000,
            'qty' => 1,
        ]);

        $nameFirst = $this->requestPost('/api/recurring-invoices/' . $invoiceId . '/lines', [
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

    /**
     * A position is the index the recurring invoice gave the line, not a value the caller
     * carries, so a `position` in the payload is ignored.
     */
    public function testPositionIsReadOnly(): void
    {
        ['id' => $invoiceId, 'lines' => $lines] = $this->createRecurringInvoiceWithLines('First', 'Second');

        self::assertSame([0, 1], array_column($lines, 'position'));

        $patched = $this->requestPatch(
            '/api/recurring-invoices/' . $invoiceId . '/line/' . $lines[1]['id'],
            ['position' => 0]
        );

        self::assertSame(1, $patched['position']);
    }

    /**
     * The recurring lines reach their owner through a different association than invoice lines
     * do, and the callback that closes the gap is an override — so it is the one most easily
     * left behind.
     */
    public function testDeletingALineRenumbersTheOnesAfterIt(): void
    {
        ['id' => $invoiceId, 'lines' => $lines] = $this->createRecurringInvoiceWithLines('First', 'Second', 'Third');

        $this->requestDelete('/api/recurring-invoices/' . $invoiceId . '/line/' . $lines[1]['id']);

        self::assertSame(0, $this->requestGet('/api/recurring-invoices/' . $invoiceId . '/line/' . $lines[0]['id'])['position']);
        self::assertSame(1, $this->requestGet('/api/recurring-invoices/' . $invoiceId . '/line/' . $lines[2]['id'])['position']);
    }

    /**
     * @return array{id: string, lines: list<array<string, mixed>>}
     */
    private function createRecurringInvoiceWithLines(string ...$descriptions): array
    {
        $client = ClientFactory::createOne();

        $invoice = $this->requestPostExpecting(
            '/api/recurring-invoices',
            [
                'client' => $this->getIriFromResource($client),
                'users' => [$this->getIriFromResource(ContactFactory::createOne(['client' => $client]))],
                'dateStart' => date(DateTimeInterface::ATOM),
                'recurringOptions' => [
                    'type' => ScheduleRecurringType::WEEKLY,
                    'endType' => ScheduleEndType::AFTER,
                    'days' => [4, 5],
                    'endOccurrence' => 1,
                ],
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
            RecurringInvoice::class,
        );

        return ['id' => $invoice['id'], 'lines' => $invoice['lines']];
    }
}
