<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle\Tests\Builder;

use CSBill\MenuBundle\Builder\MenuBuilder;
use Mockery as M;

class MenuBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoke()
    {
        $builder = M::mock('CSBill\MenuBundle\Builder\BuilderInterface');
        $item = M::mock('CSBill\MenuBundle\ItemInterface');

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
        $builder = M::mock('CSBill\MenuBundle\Builder\BuilderInterface', ['validate' => false]);
        $item = M::mock('CSBill\MenuBundle\ItemInterface');

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
        $builder = M::mock('CSBill\MenuBundle\Builder\BuilderInterface, Symfony\Component\DependencyInjection\ContainerAwareInterface', ['validate' => false]);
        $item = M::mock('CSBill\MenuBundle\ItemInterface');

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
