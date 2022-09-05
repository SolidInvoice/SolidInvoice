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

namespace SolidInvoice\MenuBundle\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Builder\BuilderInterface;
use SolidInvoice\MenuBundle\Provider;
use SolidInvoice\MenuBundle\Storage\MenuStorageInterface;
use SplPriorityQueue;

class ProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGet(): void
    {
        $storage = M::mock(MenuStorageInterface::class);

        $provider = new Provider($storage);

        $q = new SplPriorityQueue();
        $q->insert('def', 0);
        $storage->shouldReceive('get')
            ->with('abc')
            ->andReturn($q);

        self::assertSame($q, $provider->get('abc', []));

        $storage->shouldHaveReceived('get')
            ->with('abc');
    }

    public function testHas(): void
    {
        $storage = M::mock(MenuStorageInterface::class);

        $provider = new Provider($storage);

        $storage->shouldReceive('has')
            ->with('abc')
            ->andReturn(true);

        self::assertTrue($provider->has('abc', []));

        $storage->shouldHaveReceived('has')
            ->with('abc');
    }

    public function testAddBuilder(): void
    {
        $queue = M::mock(\SplPriorityQueue::class);
        $storage = M::mock(MenuStorageInterface::class);

        $provider = new Provider($storage);

        $class = M::mock(BuilderInterface::class);
        $method = 'abc';

        $storage->shouldReceive('get')
            ->with('abc')
            ->andReturn($queue);

        $queue->shouldReceive('insert');

        $provider->addBuilder($class, 'abc', $method, 120);

        $storage->shouldHaveReceived('get')
            ->with('abc');

        $queue->shouldHaveReceived('insert');
    }
}
