<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle\Tests\Storage;

use CSBill\MenuBundle\Storage\MenuStorage;

class MenuStorageTest extends \PHPUnit_Framework_TestCase
{
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

        $this->assertFalse($storage->has($name1));
        $this->assertFalse($storage->has($name2));
        $this->assertFalse($storage->has($name3));

        $this->assertInstanceOf('SplPriorityQueue', $storage->get($name1));
        $this->assertInstanceOf('SplPriorityQueue', $storage->get($name2));
        $this->assertInstanceOf('SplPriorityQueue', $storage->get($name3));

        $this->assertSame($storage->get($name1), $storage->get($name1));
        $this->assertSame($storage->get($name2), $storage->get($name2));
        $this->assertSame($storage->get($name3), $storage->get($name3));

        $this->assertNotSame($storage->get($name1), $storage->get($name2));
        $this->assertNotSame($storage->get($name1), $storage->get($name3));
        $this->assertNotSame($storage->get($name2), $storage->get($name3));

        $this->assertTrue($storage->has($name1));
        $this->assertTrue($storage->has($name2));
        $this->assertTrue($storage->has($name3));
    }

    public function storageItems()
    {
        return [
            ['a', 'b', 'c'],
        ];
    }
}