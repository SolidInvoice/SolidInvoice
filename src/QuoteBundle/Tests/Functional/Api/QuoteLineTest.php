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

use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class QuoteLineTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Line::class;
    }

    public function testCreate(): void
    {
        $quote = QuoteFactory::createOne()->_real();
        $quoteId = $quote->getId()->toString();

        $lineData = [
            'description' => 'Item 1',
            'price' => 1000,
            'qty' => 2.0,
        ];

        $result = $this->requestPost('/api/quotes/' . $quoteId . '/lines', $lineData);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id']));
        self::assertSame('Item 1', $result['description']);
        self::assertEquals(2.0, $result['qty']);
        self::assertArrayHasKey('total', $result);
    }

    public function testGet(): void
    {
        $quote = QuoteFactory::createOne()->_real();
        $quoteId = $quote->getId()->toString();

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
        $quote = QuoteFactory::createOne()->_real();
        $quoteId = $quote->getId()->toString();

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
        $quote = QuoteFactory::createOne()->_real();
        $quoteId = $quote->getId()->toString();

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
        $quote = QuoteFactory::createOne()->_real();
        $quoteId = $quote->getId()->toString();

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
    }
}
