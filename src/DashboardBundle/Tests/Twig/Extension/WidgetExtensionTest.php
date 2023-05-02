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

namespace SolidInvoice\DashboardBundle\Tests\Twig\Extension;

use Mockery\MockInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DashboardBundle\Twig\Extension\WidgetExtension;
use SolidInvoice\DashboardBundle\WidgetFactory;
use SolidInvoice\DashboardBundle\Widgets\WidgetInterface;
use SplPriorityQueue;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

class WidgetExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private WidgetExtension $extension;

    private MockInterface|WidgetFactory $factory;

    protected function setUp(): void
    {
        $this->factory = Mockery::mock(WidgetFactory::class);
        $this->extension = new WidgetExtension($this->factory);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('render_dashboard_widget', $functions[0]->getName());
    }

    public function testRenderDashboardWidget(): void
    {
        $widget = Mockery::mock(
            WidgetInterface::class,
            [
                'getTemplate' => 'test_template.html.twig',
                'getData' => ['a' => '1', 'b' => '2', 'c' => '3'],
            ]
        );

        $environment = new Environment(new ArrayLoader(['test_template.html.twig' => '{{ a }}{{ b }}{{ c }}']));

        $q = new SplPriorityQueue();
        $q->insert($widget, 0);
        $this->factory
            ->shouldReceive('get')
            ->once()
            ->with('top')
            ->andReturn($q);

        $content = $this->extension->renderDashboardWidget($environment, 'top');

        self::assertSame('123', $content);
    }
}
