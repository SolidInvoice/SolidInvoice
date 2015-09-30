<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu\Storage;

interface MenuStorageInterface
{
    /**
     * Checks if the storage has a builder for the specified menu.
     *
     * @param string $name
     * @param array  $options
     *
     * @return bool
     */
    public function has($name, array $options = array());

    /**
     * Returns the builder for the specified menu from the storage.
     *
     * @param string $name
     * @param array  $options
     *
     * @return \Knp\Menu\ItemInterface|\SplObjectStorage
     */
    public function get($name, array $options = array());
}
