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

namespace SolidInvoice\MenuBundle\Tests\Storage;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Storage\MenuStorage;
use SplPriorityQueue;

class MenuStorageTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider storageItems
     *
     * @param $name1
     * @param $name2
     * @param $name3
     */
    public function testStorage($name1, $name2, $name3)
    {
        $storage = new MenuStorage();

        self::assertFalse($storage->has($name1));
        self::assertFalse($storage->has($name2));
        self::assertFalse($storage->has($name3));

        self::assertInstanceOf(SplPriorityQueue::class, $storage->get($name1));
        self::assertInstanceOf(SplPriorityQueue::class, $storage->get($name2));
        self::assertInstanceOf(SplPriorityQueue::class, $storage->get($name3));

        self::assertSame($storage->get($name1), $storage->get($name1));
        self::assertSame($storage->get($name2), $storage->get($name2));
        self::assertSame($storage->get($name3), $storage->get($name3));

        self::assertNotSame($storage->get($name1), $storage->get($name2));
        self::assertNotSame($storage->get($name1), $storage->get($name3));
        self::assertNotSame($storage->get($name2), $storage->get($name3));

        self::assertTrue($storage->has($name1));
        self::assertTrue($storage->has($name2));
        self::assertTrue($storage->has($name3));
    }

    public function storageItems()
    {
        return [
            ['a', 'b', 'c'],
        ];
    }
}
