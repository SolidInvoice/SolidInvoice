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

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder;

use Closure;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Query;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Query
 */
final class QueryTest extends TestCase
{
    private Query $query;

    private QueryBuilder&MockObject $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = new Query($this->queryBuilder, 'e');
    }

    public function testGetQueryBuilder(): void
    {
        self::assertSame($this->queryBuilder, $this->query->getQueryBuilder());
    }

    public function testSetQueryBuilder(): void
    {
        $newBuilder = $this->createMock(QueryBuilder::class);
        $newBuilder->method('getRootAliases')->willReturn(['alias']);

        $result = $this->query->setQueryBuilder($newBuilder);

        self::assertSame($this->query, $result);
        self::assertSame($newBuilder, $this->query->getQueryBuilder());
        self::assertSame('alias', $this->query->getRootAlias());
    }

    public function testGetRootAlias(): void
    {
        self::assertSame('e', $this->query->getRootAlias());
    }

    public function testSetRootAlias(): void
    {
        $result = $this->query->setRootAlias('custom');

        self::assertSame($this->query, $result);
        self::assertSame('custom', $this->query->getRootAlias());
    }

    public function testBeforeQuery(): void
    {
        $callback = static fn () => 'before';

        $result = $this->query->beforeQuery($callback);

        self::assertSame($this->query, $result);
        self::assertSame($callback, $this->query->getCallback(Query::BEFORE_QUERY));
    }

    public function testAfterQuery(): void
    {
        $callback = static fn () => 'after';

        $result = $this->query->afterQuery($callback);

        self::assertSame($this->query, $result);
        self::assertSame($callback, $this->query->getCallback(Query::AFTER_QUERY));
    }

    public function testGetCallbacksReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->query->getCallbacks());
    }

    public function testGetCallbacksReturnsAllCallbacks(): void
    {
        $beforeCallback = static fn () => 'before';
        $afterCallback = static fn () => 'after';

        $this->query->beforeQuery($beforeCallback);
        $this->query->afterQuery($afterCallback);

        $callbacks = $this->query->getCallbacks();

        self::assertCount(2, $callbacks);
        self::assertArrayHasKey(Query::BEFORE_QUERY, $callbacks);
        self::assertArrayHasKey(Query::AFTER_QUERY, $callbacks);
        self::assertSame($beforeCallback, $callbacks[Query::BEFORE_QUERY]);
        self::assertSame($afterCallback, $callbacks[Query::AFTER_QUERY]);
    }

    public function testGetCallbackReturnsNullForUnsetCallback(): void
    {
        self::assertNull($this->query->getCallback(Query::BEFORE_QUERY));
        self::assertNull($this->query->getCallback(Query::AFTER_QUERY));
        self::assertNull($this->query->getCallback('nonexistent'));
    }

    public function testCallbacksAreClosure(): void
    {
        $this->query->beforeQuery(static fn () => 'test');

        $callback = $this->query->getCallback(Query::BEFORE_QUERY);
        self::assertInstanceOf(Closure::class, $callback);
    }

    public function testBeforeQueryConstant(): void
    {
        self::assertSame('beforeQuery', Query::BEFORE_QUERY);
    }

    public function testAfterQueryConstant(): void
    {
        self::assertSame('afterQuery', Query::AFTER_QUERY);
    }

    public function testCallbackCanBeOverwritten(): void
    {
        $firstCallback = static fn () => 'first';
        $secondCallback = static fn () => 'second';

        $this->query->beforeQuery($firstCallback);
        $this->query->beforeQuery($secondCallback);

        self::assertSame($secondCallback, $this->query->getCallback(Query::BEFORE_QUERY));
    }
}
