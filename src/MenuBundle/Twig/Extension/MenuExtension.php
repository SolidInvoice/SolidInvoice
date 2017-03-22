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

namespace CSBill\MenuBundle\Twig\Extension;

use CSBill\MenuBundle\RendererInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Twig_Extension;

class MenuExtension extends Twig_Extension
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
     *
     * @param RendererInterface $renderer
     */
    public function setRenderer(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Sets the provider for the menu.
     *
     * @param MenuProviderInterface $provider
     */
    public function setProvider(MenuProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('menu', [$this, 'renderMenu'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders a menu in a specific location.
     *
     * @param string $location The location on the page to render the menu
     * @param array  $options
     *
     * @return string
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
        return 'csbill_menu.twig.extension';
    }
}
