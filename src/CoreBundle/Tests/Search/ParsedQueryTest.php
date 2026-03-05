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
use SolidInvoice\CoreBundle\Search\ParsedQuery;

final class ParsedQueryTest extends TestCase
{
    public function testDefaultsToAllIndicesAndNoFilters(): void
    {
        $q = new ParsedQuery('acme');

        self::assertSame('acme', $q->fulltext);
        self::assertSame([], $q->indices);
        self::assertSame([], $q->indexFilters);
        self::assertSame([], $q->sort);
        self::assertSame(5, $q->hitsPerIndex);
    }

    public function testWithAllParameters(): void
    {
        $q = new ParsedQuery(
            fulltext: 'acme',
            indices: ['invoices', 'quotes'],
            indexFilters: ['invoices' => ['status = "paid"'], 'quotes' => ['status = "paid"']],
            sort: ['total:asc'],
            hitsPerIndex: 10,
        );

        self::assertSame(['invoices', 'quotes'], $q->indices);
        self::assertSame(['invoices' => ['status = "paid"'], 'quotes' => ['status = "paid"']], $q->indexFilters);
        self::assertSame(['total:asc'], $q->sort);
        self::assertSame(10, $q->hitsPerIndex);
    }
}
