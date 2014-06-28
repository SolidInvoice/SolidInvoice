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

use Knp\Menu\Provider\MenuProviderInterface;
use CSBill\CoreBundle\Menu\Builder\MenuBuilder;
use CSBill\CoreBundle\Menu\Builder\BuilderInterface;
use CSBill\CoreBundle\Menu\Storage\MenuStorageInterface;

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
     * Gets the storage for the specific menu
     *
     * @param string $name
     * @param array  $options
     *
     * @return \Knp\Menu\ItemInterface|\SplObjectStorage
     */
    public function get($name, array $options = array())
    {
        return $this->storage->get($name, $options);
    }

    /**
     * Checks if the storage has builders for the specified menu
     *
     * @param string $name
     * @param array  $options
     *
     * @return bool
     */
    public function has($name, array $options = array())
    {
        return $this->storage->has($name, $options);
    }

    /**
     * Adds a builder to the storage
     *
     * @param BuilderInterface $class
     * @param string           $name   The name of the menu the builder should be attached to
     * @param string           $method The method to call to build the menu
     */
    public function addBuilder(BuilderInterface $class, $name, $method)
    {
        $builder = new MenuBuilder($class, $method);

        $this->storage->get($name)->attach($builder);
    }
}
