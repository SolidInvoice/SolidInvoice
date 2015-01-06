<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = $this->getMock('CSBill\DashboardBundle\WidgetFactory');
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
        $widget = $this->getMock('CSBill\DashboardBundle\Widgets\WidgetInterface');
        $widgets = array(
            $widget
        );
        $environment = $this->getMock('Twig_Environment');

        $this->factory
            ->expects($this->once())
            ->method('get')
            ->with('top')
            ->will($this->returnValue($widgets));

        $environment
            ->expects($this->once())
            ->method('render')
            ->with('test_template.html.twig', array('a' => '1', 'b' => '2', 'c' => '3'))
            ->will($this->returnValue('123'));

        $widget
            ->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue('test_template.html.twig'));

        $widget
            ->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(array('a' => '1', 'b' => '2', 'c' => '3')));

        $this->extension->initRuntime($environment);
        $content = $this->extension->renderDashboardWidget('top');

        $this->assertSame('123', $content);
    }
}
