<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle\Tests;

use CSBill\MenuBundle\Provider;
use Mockery as M;

class ProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $storage = M::mock('CSBill\MenuBundle\Storage\MenuStorageInterface');

        $provider = new Provider($storage);

        $q = new \SplPriorityQueue();
        $q->insert('def', 0);
        $storage->shouldReceive('get')
            ->with('abc')
            ->andReturn($q);

        $this->assertSame($q, $provider->get('abc', []));

        $storage->shouldHaveReceived('get')
            ->with('abc');
    }

    public function testHas()
    {
        $storage = M::mock('CSBill\MenuBundle\Storage\MenuStorageInterface');

        $provider = new Provider($storage);

        $storage->shouldReceive('has')
            ->with('abc')
            ->andReturn(true);

        $this->assertTrue($provider->has('abc', []));

        $storage->shouldHaveReceived('has')
            ->with('abc');
    }

    public function testAddBuilder()
    {
        $queue = M::mock('SplPriorityQueue');
        $storage = M::mock('CSBill\MenuBundle\Storage\MenuStorageInterface');

        $provider = new Provider($storage);

        $class = M::mock('CSBill\MenuBundle\Builder\BuilderInterface');
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
