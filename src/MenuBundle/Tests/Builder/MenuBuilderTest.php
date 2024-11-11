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

use Knp\Menu\FactoryInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Builder\BuilderInterface;
use SolidInvoice\MenuBundle\Builder\MenuBuilder;
use SolidInvoice\MenuBundle\MenuItem;

class MenuBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInvoke(): void
    {
        $builder = M::mock(BuilderInterface::class);
        $item = new MenuItem('', M::mock(FactoryInterface::class));

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

    public function testInvokeFail(): void
    {
        $builder = M::mock(BuilderInterface::class, ['validate' => false]);
        $item = new MenuItem('', M::mock(FactoryInterface::class));

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

    public function testContainer(): void
    {
        $builder = M::mock(BuilderInterface::class, ['validate' => false]);
        $item = new MenuItem('', M::mock(FactoryInterface::class));

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
