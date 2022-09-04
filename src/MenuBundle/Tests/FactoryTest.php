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

use Knp\Menu\Factory\ExtensionInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Factory;
use SolidInvoice\MenuBundle\MenuItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAddExtension()
    {
        $factory = new Factory(M::mock(UrlGeneratorInterface::class));

        $extension = M::mock(ExtensionInterface::class);

        $factory->addExtension($extension);

        self::assertCount(3, $factory->getExtensions());
    }

    public function testCreateItem()
    {
        $generator = M::mock(UrlGeneratorInterface::class);

        $generator->shouldReceive('generate')
            ->once()
            ->with('test_route', [], 1)
            ->andReturn('/test/route');

        $factory = new Factory($generator);

        $item = $factory->createItem('abc', ['label' => 'def', 'route' => 'test_route']);

        self::assertInstanceOf(MenuItem::class, $item);

        self::assertSame('/test/route', $item->getUri());
        self::assertSame('def', $item->getLabel());

        $generator->shouldHaveReceived('generate')
            ->with('test_route', [], 1);
    }
}
