<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MenuBundle\Tests;

use SolidInvoice\MenuBundle\Factory;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAddExtension()
    {
        $factory = new Factory(M::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface'));

        $extension = M::mock('Knp\Menu\Factory\ExtensionInterface');

        $factory->addExtension($extension);

        $this->assertCount(3, self::getObjectAttribute($factory, 'extensions'));
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

        $this->assertInstanceOf('SolidInvoice\MenuBundle\MenuItem', $item);

        $this->assertSame('/test/route', $item->getUri());
        $this->assertSame('def', $item->getLabel());

        $generator->shouldHaveReceived('generate')
            ->with('test_route', [], 1);
    }
}
