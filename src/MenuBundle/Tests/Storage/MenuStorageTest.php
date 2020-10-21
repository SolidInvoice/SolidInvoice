<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MenuBundle\Tests\Storage;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Storage\MenuStorage;

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

        static::assertFalse($storage->has($name1));
        static::assertFalse($storage->has($name2));
        static::assertFalse($storage->has($name3));

        static::assertInstanceOf('SplPriorityQueue', $storage->get($name1));
        static::assertInstanceOf('SplPriorityQueue', $storage->get($name2));
        static::assertInstanceOf('SplPriorityQueue', $storage->get($name3));

        static::assertSame($storage->get($name1), $storage->get($name1));
        static::assertSame($storage->get($name2), $storage->get($name2));
        static::assertSame($storage->get($name3), $storage->get($name3));

        static::assertNotSame($storage->get($name1), $storage->get($name2));
        static::assertNotSame($storage->get($name1), $storage->get($name3));
        static::assertNotSame($storage->get($name2), $storage->get($name3));

        static::assertTrue($storage->has($name1));
        static::assertTrue($storage->has($name2));
        static::assertTrue($storage->has($name3));
    }

    public function storageItems()
    {
        return [
            ['a', 'b', 'c'],
        ];
    }
}
