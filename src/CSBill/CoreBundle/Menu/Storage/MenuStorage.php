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

class MenuStorage implements MenuStorageInterface
{
    /**
     * @var array
     */
    protected $list = array();

    /**
     * (non-PHPDoc)
     *
     * @param string $name
     */
    public function has($name)
    {
        return isset($this->list[$name]);
    }

    /**
     * (non-PHPDoc)
     *
     * @param  string            $name
     * @return \SplObjectStorage
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            $this->list[$name] = new \SplObjectStorage;
        }

        return $this->list[$name];
    }
}
