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
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use SplPriorityQueue;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @see \SolidInvoice\MenuBundle\Tests\FactoryTest
 */
class Factory extends MenuFactory
{
    /**
     * @var SplPriorityQueue<int, ExtensionInterface>
     */
    protected $extensions;

    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->extensions = new SplPriorityQueue();

        parent::__construct();

        $this->addExtension(new RoutingExtension($generator));
    }

    public function createItem(string $name, array $options = []): ItemInterface
    {
        $item = new MenuItem($name, $this);

        foreach (clone $this->extensions as $extension) {
            $options = $extension->buildOptions($options);

            $extension->buildItem($item, $options);
        }

        return $item;
    }

    public function addExtension(ExtensionInterface $extension, int $priority = 0): void
    {
        $this->extensions->insert($extension, $priority);
    }

    /**
     * @return SplPriorityQueue<int, ExtensionInterface>
     */
    public function getExtensions(): SplPriorityQueue
    {
        return $this->extensions;
    }
}
