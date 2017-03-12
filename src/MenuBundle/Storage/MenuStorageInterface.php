<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle\Storage;

interface MenuStorageInterface
{
    /**
     * Checks if the storage has a builder for the specified menu.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Returns the builder for the specified menu from the storage.
     *
     * @param string $name
     *
     * @return \SplPriorityQueue
     */
    public function get($name);
}
