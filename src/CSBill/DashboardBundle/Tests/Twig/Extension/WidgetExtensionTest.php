<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Tests\Twig\Extension;

use CSBill\DashboardBundle\Twig\Extension\WidgetExtension;

class WidgetExtensionTest extends \PHPUnit_Framework_TestCase
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

    public function testInitRuntime()
    {
        $env = new \Twig_Environment();

        $this->extension->initRuntime($env);

        $this->assertSame($env, \PHPUnit_Framework_Assert::readAttribute($this->extension, 'environment'));
    }

    public function testRenderDashboardWidget()
    {
        $widget = \Mockery::mock(
            'CSBill\DashboardBundle\Widgets\WidgetInterface',
            array(
                'getTemplate' => 'test_template.html.twig',
                'getData' => array('a' => '1', 'b' => '2', 'c' => '3'),
            )
        );

        $environment = \Mockery::mock('Twig_Environment');

        $this->factory
            ->shouldReceive('get')
            ->once()
            ->with('top')
            ->andReturn(array($widget));

        $environment
            ->shouldReceive('render')
            ->once()
            ->with('test_template.html.twig', array('a' => '1', 'b' => '2', 'c' => '3'))
            ->andReturn('123');

        $this->extension->initRuntime($environment);
        $content = $this->extension->renderDashboardWidget('top');

        $this->assertSame('123', $content);
    }
}
