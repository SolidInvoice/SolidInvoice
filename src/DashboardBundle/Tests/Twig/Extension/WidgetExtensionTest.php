<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Tests\Twig\Extension;

use CSBill\DashboardBundle\Twig\Extension\WidgetExtension;

class WidgetExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WidgetExtension
     */
    private $extension;

    /**
     * @var \Mockery\Mock
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = \Mockery::mock('CSBill\DashboardBundle\WidgetFactory');
        $this->extension = new WidgetExtension($this->factory);
    }

    public function testGetName()
    {
        $this->assertSame('dashboard_widget_extension', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        /** @var \Twig_SimpleFunction[] $functions */
        $functions = $this->extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf('Twig_SimpleFunction', $functions[0]);
        $this->assertSame('render_dashboard_widget', $functions[0]->getName());
    }

    public function testRenderDashboardWidget()
    {
        $widget = \Mockery::mock(
            'CSBill\DashboardBundle\Widgets\WidgetInterface',
            [
                'getTemplate' => 'test_template.html.twig',
                'getData' => ['a' => '1', 'b' => '2', 'c' => '3'],
            ]
        );

        $environment = \Mockery::mock('Twig_Environment');

        $q = new \SplPriorityQueue();
        $q->insert($widget, 0);
        $this->factory
            ->shouldReceive('get')
            ->once()
            ->with('top')
            ->andReturn($q);

        $environment
            ->shouldReceive('render')
            ->once()
            ->with('test_template.html.twig', ['a' => '1', 'b' => '2', 'c' => '3'])
            ->andReturn('123');

        $content = $this->extension->renderDashboardWidget($environment, 'top');

        $this->assertSame('123', $content);
    }
}
