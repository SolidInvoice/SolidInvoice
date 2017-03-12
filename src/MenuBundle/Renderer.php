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

namespace CSBill\MenuBundle;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface as Item;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Matcher\Voter\RouteVoter;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\EngineInterface;

class Renderer extends ListRenderer implements RendererInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * Constructor.
     *
     * @param RequestStack     $requestStack
     * @param FactoryInterface $factory
     */
    public function __construct(RequestStack $requestStack, FactoryInterface $factory)
    {
        $this->factory = $factory;

        $matcher = new Matcher();
        $matcher->addVoter(new RouteVoter($requestStack->getCurrentRequest()));

        parent::__construct($matcher, ['allow_safe_labels' => true, 'currentClass' => 'active']);
    }

    /**
     * Renders a menu at a specific location.
     *
     * @param \SplPriorityQueue $storage
     * @param array             $options
     *
     * @return string
     */
    public function build(\SplPriorityQueue $storage, array $options = [])
    {
        $menu = $this->factory->createItem('root');

        if (isset($options['attr'])) {
            $menu->setChildrenAttributes($options['attr']);
        } else {
            $menu->setChildrenAttributes(['class' => 'nav nav-pills nav-stacked']);
        }

        foreach ($storage as $builder) {
            /* @var \CSBill\MenuBundle\Builder\MenuBuilder $builder */
            $builder->setContainer($this->container);
            $builder->invoke($menu, $options);
        }

        return $this->render($menu, $options);
    }

    /**
     * Renders all of the children of this menu.
     *
     * This calls ->renderItem() on each menu item, which instructs each
     * menu item to render themselves as an <li> tag (with nested ul if it
     * has children).
     * This method updates the depth for the children.
     *
     * @param Item  $item
     * @param array $options The options to render the item
     *
     * @return string
     */
    protected function renderChildren(Item $item, array $options)
    {
        // render children with a depth - 1
        if (null !== $options['depth']) {
            $options['depth'] = $options['depth'] - 1;
        }

        $html = '';
        foreach ($item->getChildren() as $child) {
            /* @var \CSBill\MenuBundle\MenuItem $child */
            if ($child->isDivider()) {
                $html .= $this->renderDivider($child, $options);
            } else {
                $html .= $this->renderItem($child, $options);
            }
        }

        return $html;
    }

    /**
     * @param Item  $item
     * @param array $options
     *
     * @return string
     */
    protected function renderDivider(Item $item, array $options = [])
    {
        return $this->format(
            '<li'.$this->renderHtmlAttributes(['class' => 'divider'.$item->getExtra('divider')]).'>',
            'li',
            $item->getLevel(),
            $options
        );
    }

    /**
     * Renders the menu label.
     *
     * @param Item  $item
     * @param array $options
     *
     * @return string
     */
    protected function renderLabel(Item $item, array $options)
    {
        $icon = '';
        if ($item->getExtra('icon')) {
            $icon = $this->renderIcon($item->getExtra('icon'));
        }

        $translator = $this->container->get('translator');

        if ($options['allow_safe_labels'] && $item->getExtra('safe_label', false)) {
            return $icon.$translator->trans($item->getLabel());
        }

        return $icon.$this->escape($translator->trans($item->getLabel()));
    }

    /**
     * Renders an icon in the menu.
     *
     * @param string $icon
     *
     * @return string
     */
    protected function renderIcon($icon)
    {
        return $this->container->get('templating')->render('CSBillMenuBundle::icon.html.twig', ['icon' => $icon]);
    }
}
