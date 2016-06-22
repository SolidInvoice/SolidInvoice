<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle\Tests;

use CSBill\MenuBundle\Factory;
use Mockery as M;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testAddExtension()
    {
        $factory = new Factory(M::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface'));

        $extension = M::mock('Knp\Menu\Factory\ExtensionInterface');

        $factory->addExtension($extension);

        $this->assertCount(3, \PHPUnit_Framework_Assert::getObjectAttribute($factory, 'extensions'));
    }

    public function testCreateItem()
    {
        $generator = M::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $generator->shouldReceive('generate')
            ->once()
            ->with('test_route', [], 1)
            ->andReturn('/test/route');

        $factory = new Factory($generator);

        $item = $factory->createItem('abc', ['label' => 'def', 'route' => 'test_route']);

        $this->assertInstanceOf('CSBill\MenuBundle\MenuItem', $item);

        $this->assertSame('/test/route', $item->getUri());
        $this->assertSame('def', $item->getLabel());

        $generator->shouldHaveReceived('generate')
            ->with('test_route', [], 1);
    }
}
