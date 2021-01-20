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
use SolidInvoice\MenuBundle\Factory;

class FactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAddExtension()
    {
        $factory = new Factory(M::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface'));

        $extension = M::mock('Knp\Menu\Factory\ExtensionInterface');

        $factory->addExtension($extension);

        static::assertCount(3, $factory->getExtensions());
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

        static::assertInstanceOf('SolidInvoice\MenuBundle\MenuItem', $item);

        static::assertSame('/test/route', $item->getUri());
        static::assertSame('def', $item->getLabel());

        $generator->shouldHaveReceived('generate')
            ->with('test_route', [], 1);
    }
}
