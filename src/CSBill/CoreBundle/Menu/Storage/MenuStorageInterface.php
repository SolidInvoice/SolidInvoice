<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Menu\Storage;

interface MenuStorageInterface
{
    /**
     * Checks if the storage has a builder for the specified menu
     *
     * @param string $name
     * @return boolean
     */
    public function has($name);

    /**
     * Returns the builder for the specified menu from the storage
     *
     * @param string $name
     * @return \SplObjectStorage
     */
    public function get($name);
}
