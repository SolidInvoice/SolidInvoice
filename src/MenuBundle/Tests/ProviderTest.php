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

use Knp\Menu\FactoryInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Builder\BuilderInterface;
use SolidInvoice\MenuBundle\MenuItem;
use SolidInvoice\MenuBundle\Provider;

class ProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGet(): void
    {
        $factory = M::mock(FactoryInterface::class);

        $provider = new Provider($factory);
        $builder = M::mock(BuilderInterface::class);
        $provider->addBuilder($builder, 'abc', 'foo', 1);

        $q = new MenuItem('root', $factory);
        $factory->shouldReceive('createItem')
            ->once()
            ->with('root')
            ->andReturn($q);

        $builder->shouldReceive('validate')
            ->once()
            ->andReturn(true);

        $builder->shouldReceive('foo')
            ->once()
            ->withArgs([$q, []]);

        self::assertSame($q, $provider->get('abc', []));
    }

    public function testHas(): void
    {
        $factory = M::mock(FactoryInterface::class);

        $provider = new Provider($factory);
        $builder = M::mock(BuilderInterface::class);
        $provider->addBuilder($builder, 'abc', 'foo', 1);

        self::assertTrue($provider->has('abc', []));
        self::assertFalse($provider->has('def', []));
    }
}
