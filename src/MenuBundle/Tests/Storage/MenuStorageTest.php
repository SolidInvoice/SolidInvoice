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

use SolidInvoice\MenuBundle\Storage\MenuStorage;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

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
