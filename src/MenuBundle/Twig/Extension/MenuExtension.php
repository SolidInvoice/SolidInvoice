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

namespace SolidInvoice\MenuBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Knp\Menu\Provider\MenuProviderInterface;
use SolidInvoice\MenuBundle\RendererInterface;

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
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu', [$this, 'renderMenu'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders a menu in a specific location.
     *
     * @param string $location The location on the page to render the menu
     */
    public function renderMenu(string $location, array $options = []): string
    {
        /** @var \SplPriorityQueue $menu */
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
