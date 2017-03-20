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

namespace CSBill\MenuBundle\Tests\Twig\Extension;

use CSBill\MenuBundle\Twig\Extension\MenuExtension;
use Mockery as M;

class MenuExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MenuExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->extension = new MenuExtension();
    }

    public function testGetName()
    {
        $this->assertSame('csbill_menu.twig.extension', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();

        $this->assertTrue(is_array($functions));

        foreach ($functions as $function) {
            $this->assertInstanceOf('Twig_SimpleFunction', $function);
        }
    }

    public function testRenderMenu()
    {
        $provider = M::mock('Knp\Menu\Provider\MenuProviderInterface');
        $this->extension->setProvider($provider);

        $renderer = M::mock('CSBill\MenuBundle\RendererInterface');
        $this->extension->setRenderer($renderer);

        $location = 'abc';
        $menu = new \SplPriorityQueue();

        $provider->shouldReceive('get')
            ->once()
            ->with($location)
            ->andReturn($menu);

        $renderer->shouldReceive('build')
            ->once()
            ->with($menu, ['a' => 'b'])
            ->andReturn('123');

        $this->assertSame('123', $this->extension->renderMenu($location, ['a' => 'b']));

        $provider->shouldHaveReceived('get')
            ->once()
            ->with($location);

        $renderer->shouldHaveReceived('build')
            ->once()
            ->with($menu, ['a' => 'b']);
    }
}
