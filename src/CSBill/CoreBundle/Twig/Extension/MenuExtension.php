<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Twig\Extension;

use Twig_Extension;
use Twig_Function_Method;
use Knp\Menu\Renderer\RendererInterface;
use Knp\Menu\Provider\MenuProviderInterface;

class MenuExtension extends Twig_Extension
{
    /**
     * @var RendererInterface $renderer
     */
    protected $renderer;

    /**
     * @var MenuProviderInterface $provider
     */
    protected $provider;

    /**
     * Sets the renderer for the menu
     *
     * @param RendererInterface $renderer
     */
    public function setRenderer(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Sets the provider for the menu
     *
     * @param MenuProviderInterface $provider
     */
    public function setProvider(MenuProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * (non-phpdoc)
     */
    public function getFunctions()
    {
        return array(
                    'cs_menu' => new Twig_Function_Method($this, 'renderMenu', array('is_safe' => array('html')))
                );
    }

    /**
     * Renders a menu in a specific location
     *
     * @param  string $location The location on the page to render the menu
     * @param  array  $options
     * @return string
     */
    public function renderMenu($location, array $options = array())
    {
        $menu = $this->provider->get($location);

        return $this->renderer->build($menu, $options);
    }

    /**
     * (non-phpdoc)
     */
    public function getName()
    {
        return 'cs_core.twig.menu';
    }
}
