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

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\Integration\Symfony\RoutingExtension;
use Knp\Menu\MenuFactory;
use SplPriorityQueue;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Factory extends MenuFactory
{
    /**
     * @var SplPriorityQueue|ExtensionInterface[]
     */
    protected $extensions;

    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->extensions = new SplPriorityQueue();

        parent::__construct();

        $this->addExtension(new RoutingExtension($generator));
    }

    /**
     * @param string $name
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
     * @param int $priority
     */
    public function addExtension(ExtensionInterface $extension, $priority = 0)
    {
        $this->extensions->insert($extension, $priority);
    }

    /**
     * @return ExtensionInterface[]|SplPriorityQueue
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}
