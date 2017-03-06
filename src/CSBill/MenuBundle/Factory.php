<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\Integration\Symfony\RoutingExtension;
use Knp\Menu\MenuFactory;
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

        parent::__construct();

        $this->addExtension(new RoutingExtension($generator));
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return MenuItem|\Knp\Menu\MenuItem
     */
    public function createItem($name, array $options = [])
    {
        $item = new MenuItem($name, $this);

        foreach (clone $this->extensions as $extension) {
            $options = $extension->buildOptions($options);

            $extension->buildItem($item, $options);
        }

        return $item;
    }

    /**
     * Adds a factory extension.
     *
     * @param ExtensionInterface $extension
     * @param int                $priority
     */
    public function addExtension(ExtensionInterface $extension, $priority = 0)
    {
        $this->extensions->insert($extension, $priority);
    }
}
