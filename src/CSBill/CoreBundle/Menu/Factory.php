<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\Factory\CoreExtension;
use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\Silex\RoutingExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Factory extends MenuFactory
{
    /**
     * @var \SplPriorityQueue|ExtensionInterface[]
     */
    protected $extensions;

    /**
     * @param UrlGeneratorInterface $generator
     */
    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->extensions = new \SplPriorityQueue();
        $this->addExtension(new CoreExtension(), -10);
        $this->addExtension(new RoutingExtension($generator));
    }

    /**
     * @param  string                      $name
     * @param  array                       $options
     * @return MenuItem|\Knp\Menu\MenuItem
     */
    public function createItem($name, array $options = array())
    {
        foreach (clone $this->extensions as $extension) {
            $options = $extension->buildOptions($options);
        }

        $item = new MenuItem($name, $this);

        foreach (clone $this->extensions as $extension) {
            $extension->buildItem($item, $options);
        }

        return $item;
    }

    /**
     * Adds a factory extension
     *
     * @param ExtensionInterface $extension
     * @param integer            $priority
     */
    public function addExtension(ExtensionInterface $extension, $priority = 0)
    {
        $this->extensions->insert($extension, $priority);
    }
}
