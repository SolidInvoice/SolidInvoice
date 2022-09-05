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

namespace SolidInvoice\MenuBundle;

use InvalidArgumentException;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\ItemInterface as Item;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Matcher\Voter\RouteVoter;
use Knp\Menu\Renderer\ListRenderer;
use SplPriorityQueue;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Renderer extends ListRenderer implements RendererInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(RequestStack $requestStack, FactoryInterface $factory, TranslatorInterface $translator, Environment $twig)
    {
        $this->factory = $factory;
        $this->twig = $twig;
        $this->translator = $translator;

        $matcher = new class([new RouteVoter($requestStack)]) extends Matcher {
            public function isCurrent(ItemInterface $item)
            {
                $current = parent::isCurrent($item);
                $item->setCurrent($current);

                return $current;
            }
        };

        parent::__construct($matcher, ['allow_safe_labels' => true, 'currentClass' => 'active']);
    }

    /**
     * Renders a menu at a specific location.
     */
    public function build(SplPriorityQueue $storage, array $options = []): string
    {
        $menu = $this->factory->createItem('root');

        if (isset($options['attr'])) {
            $menu->setChildrenAttributes($options['attr']);
        } else {
            $menu->setChildrenAttributes(['class' => 'nav nav-pills nav-sidebar flex-column']);
        }

        foreach ($storage as $builder) {
            /** @var \SolidInvoice\MenuBundle\Builder\MenuBuilder $builder */
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
     * @param array $options The options to render the item
     */
    protected function renderChildren(Item $item, array $options): string
    {
        // render children with a depth - 1
        if (null !== $options['depth']) {
            --$options['depth'];
        }

        $html = '';
        foreach ($item->getChildren() as $child) {
            /** @var \SolidInvoice\MenuBundle\MenuItem $child */
            if ($child->isDivider()) {
                $html .= $this->renderDivider($child, $options);
            } else {
                $html .= $this->renderItem($child, $options);
            }
        }

        return $html;
    }

    protected function renderDivider(Item $item, array $options = []): string
    {
        return $this->format(
            '<li' . $this->renderHtmlAttributes(['class' => 'divider' . $item->getExtra('divider')]) . '>',
            'li',
            $item->getLevel(),
            $options
        );
    }

    /**
     * Renders the menu label.
     */
    protected function renderLabel(Item $item, array $options): string
    {
        $icon = '';
        if ($item->getExtra('icon')) {
            $icon = $this->renderIcon($item->getExtra('icon'));
        }

        if ($options['allow_safe_labels'] && $item->getExtra('safe_label', false)) {
            return $icon . $this->translator->trans($item->getLabel());
        }

        return sprintf('%s <p>%s</p>', $icon, $this->escape($this->translator->trans($item->getLabel())));
    }

    /**
     * Renders an icon in the menu.
     */
    protected function renderIcon(string $icon): string
    {
        return $this->twig->render('@SolidInvoiceMenu/icon.html.twig', ['icon' => $icon]);
    }

    protected function renderLinkElement(ItemInterface $item, array $options)
    {
        $attributes = $item->getLinkAttributes();

        $attributes['class'] .= $item->isCurrent() ? ' ' . $options['currentClass'] : '';

        return \sprintf('<a href="%s"%s>%s</a>', $this->escape($item->getUri()), $this->renderHtmlAttributes($attributes), $this->renderLabel($item, $options));
    }
}
