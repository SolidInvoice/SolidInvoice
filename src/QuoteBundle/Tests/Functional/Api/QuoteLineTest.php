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
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

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
            'description' => 'Item 1',
            'price' => 1000,
            'qty' => 2.0,
        ];

        $result = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        self::assertSame('Item 1', $result['description']);
        self::assertEquals(2.0, $result['qty']);
        self::assertArrayHasKey('total', $result);
    }

    public function testGet(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $lineData = [
            'description' => 'Test Item',
            'price' => 500,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestGet('/api/quotes/' . $quoteId . '/line/' . $lineId);

        self::assertSame('Test Item', $data['description']);
        self::assertSame($lineId, $data['id']);
        self::assertEquals(1.0, $data['qty']);
    }

    public function testEdit(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $lineData = [
            'description' => 'Original Item',
            'price' => 300,
            'qty' => 1.0,
        ];

        $created = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);
        $lineId = $created['id'];

        $data = $this->requestPatch(
            '/api/quotes/' . $quoteId . '/line/' . $lineId,
            ['description' => 'Updated Item']
        );

        self::assertSame('Updated Item', $data['description']);
        self::assertSame($lineId, $data['id']);
    }

    public function testDelete(): void
    {
        $quote = QuoteFactory::createOne();
        $quoteId = $quote->getId()
            ->toString();

        $lineData = [
            'description' => 'Item To Delete',
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
            'description' => 'Collection Item 1',
            'price' => 100,
            'qty' => 1.0,
        ]);

        $this->requestPost('/api/quotes/' . $quoteId . '/lines', [
            'description' => 'Collection Item 2',
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
        self::assertSame(
            ['Collection Item 1', 'Collection Item 2'],
            array_column($data['member'], 'description')
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
}
