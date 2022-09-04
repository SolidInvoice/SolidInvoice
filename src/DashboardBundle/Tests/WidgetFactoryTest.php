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

namespace SolidInvoice\DashboardBundle\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DashboardBundle\WidgetFactory;
use SolidInvoice\DashboardBundle\Widgets\WidgetInterface;
use SplPriorityQueue;

class WidgetFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAdd()
    {
        $factory = new WidgetFactory();

        $widget1 = M::mock(WidgetInterface::class);
        $widget2 = M::mock(WidgetInterface::class);
        $widget3 = M::mock(WidgetInterface::class);
        $widget4 = M::mock(WidgetInterface::class);
        $widget5 = M::mock(WidgetInterface::class);

        $factory->add($widget1, 'top', 100);
        $factory->add($widget2, 'left_column', 200);
        $factory->add($widget3, 'right_column', 300);
        $factory->add($widget4, null, 400);
        $factory->add($widget5, 'left_column');

        self::assertInstanceOf(SplPriorityQueue::class, $factory->get('top'));
        self::assertInstanceOf(SplPriorityQueue::class, $factory->get('left_column'));
        self::assertInstanceOf(SplPriorityQueue::class, $factory->get('right_column'));

        self::assertCount(2, $factory->get('top'));
        self::assertCount(2, $factory->get('left_column'));
        self::assertCount(1, $factory->get('right_column'));
    }

    public function testInvalidLocation()
    {
        $factory = new WidgetFactory();

        $widget = M::mock(WidgetInterface::class);

        $this->expectException('Exception');
        $this->expectExceptionMessage('Invalid widget location: bottom');

        $factory->add($widget, 'bottom');
    }

    public function testGet()
    {
        $factory = new WidgetFactory();

        $widget1 = M::mock(WidgetInterface::class);
        $widget2 = M::mock(WidgetInterface::class);
        $widget3 = M::mock(WidgetInterface::class);
        $widget4 = M::mock(WidgetInterface::class);
        $widget5 = M::mock(WidgetInterface::class);

        $factory->add($widget1, 'top', 100);
        $factory->add($widget2, 'left_column', 200);
        $factory->add($widget3, 'right_column', 300);
        $factory->add($widget4, null, 400);
        $factory->add($widget5, 'left_column');

        $queue1 = $factory->get('top');
        self::assertInstanceOf(SplPriorityQueue::class, $queue1);
        self::assertCount(2, $queue1);
        self::assertSame($widget4, $queue1->current());
        $queue1->next();
        self::assertSame($widget1, $queue1->current());

        $queue2 = $factory->get('left_column');
        self::assertInstanceOf(SplPriorityQueue::class, $queue2);
        self::assertCount(2, $queue2);
        self::assertSame($widget2, $queue2->current());
        $queue2->next();
        self::assertSame($widget5, $queue2->current());

        $queue3 = $factory->get('right_column');
        self::assertInstanceOf(SplPriorityQueue::class, $queue3);
        self::assertCount(1, $queue3);
        self::assertSame($widget3, $queue3->current());

        $queue4 = $factory->get('bottom');
        self::assertInstanceOf(SplPriorityQueue::class, $queue4);
        self::assertCount(0, $queue4);
    }
}
