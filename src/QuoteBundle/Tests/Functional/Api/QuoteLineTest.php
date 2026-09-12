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

namespace SolidInvoice\QuoteBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use function array_column;
use function array_map;
use function count;

#[Group('functional')]
final class QuoteLineTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Line::class;
    }

    public function testCreate(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $lineData = [
            'name' => 'Item 1',
            'price' => 1000,
            'qty' => 2.0,
        ];

        $result = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        self::assertSame('Item 1', $result['name']);
        self::assertEquals(2.0, $result['qty']);
        self::assertArrayHasKey('total', $result);
    }

    public function testGet(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $lineData = [
            'name' => 'Test Item',
            'price' => 500,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestGet('/api/quotes/' . $quoteId . '/line/' . $lineId);

        self::assertSame('Test Item', $data['name']);
        self::assertSame($lineId, $data['id']);
        self::assertEquals(1.0, $data['qty']);
    }

    public function testEdit(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $lineData = [
            'name' => 'Original Item',
            'price' => 300,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestPatch(
            '/api/quotes/' . $quoteId . '/line/' . $lineId,
            ['name' => 'Updated Item']
        );

        self::assertSame('Updated Item', $data['name']);
        self::assertSame($lineId, $data['id']);
    }

    public function testDelete(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $lineData = [
            'name' => 'Item To Delete',
            'price' => 100,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);
        $lineId = $created['id'];

        $this->requestDelete('/api/quotes/' . $quoteId . '/line/' . $lineId);
    }

    public function testGetCollection(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $this->requestPost('/api/quotes/' . $quoteId . '/lines', [
            'name' => 'Collection Item 1',
            'price' => 100,
            'qty' => 1.0,
        ]);

        $this->requestPost('/api/quotes/' . $quoteId . '/lines', [
            'name' => 'Collection Item 2',
            'price' => 200,
            'qty' => 2.0,
        ]);

        $data = $this->requestGetCollection('/api/quotes/' . $quoteId . '/lines');

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
     * quote is not there is the processor's job rather than the framework's.
     */
    public function testCreateOnAMissingOwnerIs404(): void
    {
        $missingId = new Ulid()->toString();

        self::$client->request(
            method: Request::METHOD_POST,
            url: '/api/quotes/' . $missingId . '/lines',
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
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $result = $this->requestPost('/api/quotes/' . $quoteId . '/lines', [
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
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $result = $this->requestPost('/api/quotes/' . $quoteId . '/lines', [
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
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $descriptionFirst = $this->requestPost('/api/quotes/' . $quoteId . '/lines', [
            'description' => 'Consulting',
            'name' => 'Widget',
            'price' => 1000,
            'qty' => 1,
        ]);

        $nameFirst = $this->requestPost('/api/quotes/' . $quoteId . '/lines', [
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
     * A position is the index the quote gave the line, not a value the caller carries, so a
     * `position` in the payload is ignored. Were it not, two lines could claim the same slot
     * and the order of the collection would stop being a fact about it.
     */
    public function testPositionIsReadOnly(): void
    {
        ['id' => $quoteId, 'lines' => $lines] = $this->createQuoteWithLines('First', 'Second');

        self::assertSame([0, 1], array_column($lines, 'position'));

        $patched = $this->requestPatch(
            '/api/quotes/' . $quoteId . '/line/' . $lines[1]['id'],
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
        ['id' => $quoteId, 'lines' => $lines] = $this->createQuoteWithLines('First', 'Second', 'Third');

        $this->requestDelete('/api/quotes/' . $quoteId . '/line/' . $lines[1]['id']);

        self::assertSame(0, $this->requestGet('/api/quotes/' . $quoteId . '/line/' . $lines[0]['id'])['position']);
        self::assertSame(1, $this->requestGet('/api/quotes/' . $quoteId . '/line/' . $lines[2]['id'])['position']);
    }

    /**
     * Through the quote rather than a line at a time, so the fixture does not depend on what
     * `POST /quotes/{id}/lines` does with a line that is already there.
     *
     * @return array{id: string, lines: list<array<string, mixed>>}
     */
    private function createQuoteWithLines(string ...$descriptions): array
    {
        $client = ClientFactory::createOne();

        $quote = $this->requestPostExpecting(
            '/api/quotes',
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
            Quote::class,
        );

        return ['id' => $quote['id'], 'lines' => $quote['lines']];
    }
}
