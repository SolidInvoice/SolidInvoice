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

namespace SolidInvoice\CoreBundle\Tests\Search;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Search\SearchResult;

final class SearchResultTest extends TestCase
{
    public function testConstructionWithRequiredPropertiesOnly(): void
    {
        $result = new SearchResult(
            type: 'invoice',
            id: 'abc123',
            title: 'INV-001',
            subtitle: 'Acme Corp',
            url: '/invoices/abc123',
        );

        self::assertSame('invoice', $result->type);
        self::assertSame('abc123', $result->id);
        self::assertSame('INV-001', $result->title);
        self::assertSame('Acme Corp', $result->subtitle);
        self::assertSame('/invoices/abc123', $result->url);
        self::assertNull($result->status);
        self::assertNull($result->meta);
    }

    public function testConstructionWithAllProperties(): void
    {
        $result = new SearchResult(
            type: 'invoice',
            id: 'abc123',
            title: 'INV-001',
            subtitle: 'Acme Corp',
            url: '/invoices/abc123',
            status: 'paid',
            meta: '$1,500.00',
        );

        self::assertSame('invoice', $result->type);
        self::assertSame('abc123', $result->id);
        self::assertSame('INV-001', $result->title);
        self::assertSame('Acme Corp', $result->subtitle);
        self::assertSame('/invoices/abc123', $result->url);
        self::assertSame('paid', $result->status);
        self::assertSame('$1,500.00', $result->meta);
    }

    public function testStatusCanBeNull(): void
    {
        $result = new SearchResult('client', 'c1', 'Acme', '', '/clients/c1', null, '$0.00');

        self::assertNull($result->status);
    }

    public function testMetaCanBeNull(): void
    {
        $result = new SearchResult('client', 'c1', 'Acme', '', '/clients/c1', 'active', null);

        self::assertNull($result->meta);
    }

    public function testCanBeCastToArray(): void
    {
        $result = new SearchResult(
            type: 'quote',
            id: 'q1',
            title: 'Q-001',
            subtitle: 'Client X',
            url: '/quotes/q1',
            status: 'draft',
            meta: '$500.00',
        );

        $array = (array) $result;

        self::assertSame('quote', $array['type']);
        self::assertSame('q1', $array['id']);
        self::assertSame('Q-001', $array['title']);
        self::assertSame('Client X', $array['subtitle']);
        self::assertSame('/quotes/q1', $array['url']);
        self::assertSame('draft', $array['status']);
        self::assertSame('$500.00', $array['meta']);
    }

    public function testArrayCastPreservesNullValues(): void
    {
        $result = new SearchResult('payment', 'p1', 'REF-001', '', '/payments');

        $array = (array) $result;

        self::assertNull($array['status']);
        self::assertNull($array['meta']);
    }

    public function testIsReadonly(): void
    {
        $result = new SearchResult('invoice', 'i1', 'INV-001', '', '/invoices/i1');

        $this->expectException(\Error::class);

        // @phpstan-ignore-next-line
        $result->type = 'client';
    }
}
