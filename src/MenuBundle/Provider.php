<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MenuBundle;

use SolidInvoice\MenuBundle\Builder\BuilderInterface;
use SolidInvoice\MenuBundle\Builder\MenuBuilder;
use SolidInvoice\MenuBundle\Storage\MenuStorageInterface;
use Knp\Menu\Provider\MenuProviderInterface;

class Provider implements MenuProviderInterface
{
    /**
     * @var MenuStorageInterface
     */
    protected $storage;

    /**
     * @param MenuStorageInterface $storage
     */
    public function __construct(MenuStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Gets the storage for the specific menu.
     *
     * @param string $name
     * @param array  $options
     *
     * @return \SplPriorityQueue
     */
    public function get($name, array $options = []): \SplPriorityQueue
    {
        return $this->storage->get($name);
    }

    /**
     * Checks if the storage has builders for the specified menu.
     *
     * @param string $name
     * @param array  $options
     *
     * @return bool
     */
    public function has($name, array $options = []): bool
    {
        return $this->storage->has($name);
    }

    /**
     * Adds a builder to the storage.
     *
     * @param BuilderInterface $class
     * @param string           $name     The name of the menu the builder should be attached to
     * @param string           $method   The method to call to build the menu
     * @param int              $priority
     */
    public function addBuilder(BuilderInterface $class, string $name, string $method, int $priority)
    {
        $builder = new MenuBuilder($class, $method);

        $this->storage->get($name)->insert($builder, $priority);
    }
}
