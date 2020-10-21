<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DashboardBundle\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DashboardBundle\WidgetFactory;

class WidgetFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAdd()
    {
        $factory = new WidgetFactory();

        $widget1 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget2 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget3 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget4 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget5 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');

        $factory->add($widget1, 'top', 100);
        $factory->add($widget2, 'left_column', 200);
        $factory->add($widget3, 'right_column', 300);
        $factory->add($widget4, null, 400);
        $factory->add($widget5, 'left_column');

        static::assertInstanceOf('SplPriorityQueue', $factory->get('top'));
        static::assertInstanceOf('SplPriorityQueue', $factory->get('left_column'));
        static::assertInstanceOf('SplPriorityQueue', $factory->get('right_column'));

        static::assertCount(2, $factory->get('top'));
        static::assertCount(2, $factory->get('left_column'));
        static::assertCount(1, $factory->get('right_column'));
    }

    public function testInvalidLocation()
    {
        $factory = new WidgetFactory();

        $widget = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');

        $this->expectException('Exception');
        $this->expectExceptionMessage('Invalid widget location: bottom');

        $factory->add($widget, 'bottom');
    }

    public function testGet()
    {
        $factory = new WidgetFactory();

        $widget1 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget2 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget3 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget4 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');
        $widget5 = M::mock('SolidInvoice\DashboardBundle\Widgets\WidgetInterface');

        $factory->add($widget1, 'top', 100);
        $factory->add($widget2, 'left_column', 200);
        $factory->add($widget3, 'right_column', 300);
        $factory->add($widget4, null, 400);
        $factory->add($widget5, 'left_column');

        $queue1 = $factory->get('top');
        static::assertInstanceOf('SplPriorityQueue', $queue1);
        static::assertCount(2, $queue1);
        static::assertSame($widget4, $queue1->current());
        $queue1->next();
        static::assertSame($widget1, $queue1->current());

        $queue2 = $factory->get('left_column');
        static::assertInstanceOf('SplPriorityQueue', $queue2);
        static::assertCount(2, $queue2);
        static::assertSame($widget2, $queue2->current());
        $queue2->next();
        static::assertSame($widget5, $queue2->current());

        $queue3 = $factory->get('right_column');
        static::assertInstanceOf('SplPriorityQueue', $queue3);
        static::assertCount(1, $queue3);
        static::assertSame($widget3, $queue3->current());

        $queue4 = $factory->get('bottom');
        static::assertInstanceOf('SplPriorityQueue', $queue4);
        static::assertCount(0, $queue4);
    }
}
