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

use Knp\Menu\Factory\CoreExtension;
use Knp\Menu\FactoryInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\ItemInterface;
use SolidInvoice\MenuBundle\MenuItem;

class MenuItemTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAddDivider(): void
    {
        $factory = M::mock(FactoryInterface::class);
        $item = new MenuItem('test', $factory);

        $childItem = new MenuItem('test', $factory);
        $childItem->setExtra('divider', '-*');
        $factory->shouldReceive('createItem')
            ->withAnyArgs()
            ->andReturn($childItem);

        $child = $item->addDivider('*');

        self::assertInstanceOf(ItemInterface::class, $child);
        self::assertTrue($child->isDivider());
        self::assertSame('-*', $child->getExtra('divider'));
    }

    public function testAddChild(): void
    {
        $factory = M::mock(FactoryInterface::class);
        $item = new MenuItem('test', $factory);

        $factory->shouldReceive('createItem')
            ->with('abc', ['attributes' => ['class' => ' nav-item'], 'linkAttributes' => ['class' => ' nav-link']])
            ->andReturn(new MenuItem('abc', $factory));

        $child = $item->addChild('abc');

        self::assertInstanceOf(ItemInterface::class, $child);
    }

    public function testAddChildArray(): void
    {
        $factory = M::mock(FactoryInterface::class);
        $item = new MenuItem('test', $factory);

        $factory->shouldReceive('createItem')
            ->with('abc', ['attributes' => ['class' => ' nav-item'], 'linkAttributes' => ['class' => ' nav-link']])
            ->andReturn(new MenuItem('abc', $factory));

        $child = $item->addChild(['abc', []]);

        self::assertInstanceOf(ItemInterface::class, $child);
    }

    public function testIsDivider(): void
    {
        $coreExtension = new CoreExtension();
        $item = new MenuItem('test', M::mock(FactoryInterface::class));

        $options = ['extras' => ['divider' => true]];

        $coreExtension->buildItem($item, $coreExtension->buildOptions($options));

        self::assertTrue($item->isDivider());
    }

    public function testIsDividerFalse(): void
    {
        $coreExtension = new CoreExtension();
        $item = new MenuItem('test', M::mock(FactoryInterface::class));

        $coreExtension->buildItem($item, $coreExtension->buildOptions([]));

        self::assertFalse($item->isDivider());
    }
}
