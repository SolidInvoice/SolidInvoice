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

class MenuStorage implements MenuStorageInterface
{
    /**
     * @var array
     */
    protected $list = array();

    /**
     * {@inheritdoc}
     */
    public function has($name, array $options = array())
    {
        return isset($this->list[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, array $options = array())
    {
        if (!$this->has($name)) {
            $this->list[$name] = new \SplObjectStorage();
        }

        return $this->list[$name];
    }
}
