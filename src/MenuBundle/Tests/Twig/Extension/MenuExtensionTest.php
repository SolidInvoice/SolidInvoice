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

namespace SolidInvoice\MenuBundle\Tests\Twig\Extension;

use Knp\Menu\Provider\MenuProviderInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\RendererInterface;
use SolidInvoice\MenuBundle\Twig\Extension\MenuExtension;
use SplPriorityQueue;
use Twig\Extension\ExtensionInterface;
use Twig_SimpleFunction;

class MenuExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ExtensionInterface
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new MenuExtension();
    }

    public function testGetName()
    {
        self::assertSame('solidinvoice_menu.twig.extension', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();

        self::assertIsArray($functions);

        self::assertContainsOnlyInstancesOf(Twig_SimpleFunction::class, $functions);
    }

    public function testRenderMenu()
    {
        $provider = M::mock(MenuProviderInterface::class);
        $this->extension->setProvider($provider);

        $renderer = M::mock(RendererInterface::class);
        $this->extension->setRenderer($renderer);

        $location = 'abc';
        $menu = new SplPriorityQueue();

        $provider->shouldReceive('get')
            ->once()
            ->with($location)
            ->andReturn($menu);

        $renderer->shouldReceive('build')
            ->once()
            ->with($menu, ['a' => 'b'])
            ->andReturn('123');

        self::assertSame('123', $this->extension->renderMenu($location, ['a' => 'b']));

        $provider->shouldHaveReceived('get')
            ->once()
            ->with($location);

        $renderer->shouldHaveReceived('build')
            ->once()
            ->with($menu, ['a' => 'b']);
    }
}
