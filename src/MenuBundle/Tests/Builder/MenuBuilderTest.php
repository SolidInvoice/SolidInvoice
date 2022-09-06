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

namespace SolidInvoice\MenuBundle\Tests\Builder;

use SolidInvoice\MenuBundle\Builder\BuilderInterface;
use SolidInvoice\MenuBundle\ItemInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Builder\MenuBuilder;

class MenuBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInvoke()
    {
        $builder = M::mock(BuilderInterface::class);
        $item = M::mock(ItemInterface::class);

        $builder->shouldNotReceive('setContainer');

        $builder->shouldReceive('validate')
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        $builder->shouldReceive('something')
            ->once()
            ->withArgs([$item, []]);

        $menuBuilder = new MenuBuilder($builder, 'something');

        $menuBuilder->invoke($item);

        $builder->shouldHaveReceived('something', [$item, []]);
    }

    public function testInvokeFail()
    {
        $builder = M::mock(BuilderInterface::class, ['validate' => false]);
        $item = M::mock(ItemInterface::class);

        $builder->shouldNotReceive('setContainer');

        $builder->shouldReceive('validate')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        $builder->shouldReceive('something')
            ->never();

        $menuBuilder = new MenuBuilder($builder, 'something');

        $menuBuilder->invoke($item);

        $builder->shouldNotHaveReceived('something', [$item, []]);
    }

    public function testContainer()
    {
        $builder = M::mock('SolidInvoice\MenuBundle\Builder\BuilderInterface, Symfony\Component\DependencyInjection\ContainerAwareInterface', ['validate' => false]);
        $item = M::mock(ItemInterface::class);

        $builder->shouldReceive('setContainer')
            ->once()
            ->withArgs([null]);

        $builder->shouldReceive('validate')
            ->once()
            ->withNoArgs()
            ->andReturn(false);

        $builder->shouldReceive('something')
            ->never();

        $menuBuilder = new MenuBuilder($builder, 'something');

        $menuBuilder->invoke($item);

        $builder->shouldNotHaveReceived('something', [$item, []]);
    }
}
