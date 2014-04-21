<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu\Storage;

interface MenuStorageInterface
{
    /**
     * Checks if the storage has a builder for the specified menu
     *
     * @param  string  $name
     * @return boolean
     */
    public function has($name);

    /**
     * Returns the builder for the specified menu from the storage
     *
     * @param  string            $name
     * @return \SplObjectStorage
     */
    public function get($name);
}
