<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle;

use CSBill\MenuBundle\Builder\BuilderInterface;
use CSBill\MenuBundle\Builder\MenuBuilder;
use CSBill\MenuBundle\Storage\MenuStorageInterface;
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
     * @return \SplObjectStorage
     */
    public function get($name, array $options = [])
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
    public function has($name, array $options = [])
    {
        return $this->storage->has($name);
    }

    /**
     * Adds a builder to the storage.
     *
     * @param BuilderInterface $class
     * @param string           $name   The name of the menu the builder should be attached to
     * @param string           $method The method to call to build the menu
     * @param int              $priority
     */
    public function addBuilder(BuilderInterface $class, $name, $method, $priority)
    {
        $builder = new MenuBuilder($class, $method);

        $this->storage->get($name)->insert($builder, $priority);
    }
}
