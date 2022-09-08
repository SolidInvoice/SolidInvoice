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

namespace SolidInvoice\MenuBundle\Twig\Extension;

use Knp\Menu\Provider\MenuProviderInterface;
use SolidInvoice\MenuBundle\RendererInterface;
use SplPriorityQueue;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @see \SolidInvoice\MenuBundle\Tests\Twig\Extension\MenuExtensionTest
 */
class MenuExtension extends AbstractExtension
{
    /**
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * @var MenuProviderInterface
     */
    protected $provider;

    /**
     * Sets the renderer for the menu.
     */
    public function setRenderer(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Sets the provider for the menu.
     */
    public function setProvider(MenuProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu', function (string $location, array $options = []): string {
                return $this->renderMenu($location, $options);
            }, ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders a menu in a specific location.
     *
     * @param string $location The location on the page to render the menu
     */
    public function renderMenu(string $location, array $options = []): string
    {
        /** @var SplPriorityQueue $menu */
        $menu = $this->provider->get($location);

        return $this->renderer->build($menu, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'solidinvoice_menu.twig.extension';
    }
}
