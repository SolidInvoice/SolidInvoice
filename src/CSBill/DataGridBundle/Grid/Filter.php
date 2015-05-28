<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Grid;

class Filter
{
    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var array|null
     */
    protected $options;

    /**
     * @param string   $name
     * @param callable $callback
     * @param bool     $active
     * @param array    $options
     */
    public function __construct($name, callable $callback = null, $active = false, array $options = array())
    {
        $this->name = $name;
        $this->callback = $callback ?: function () {};
        $this->active = $active;

        $this->options = $options;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->callback, func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        if ($this->isActive()) {
            return isset($this->options['active_class']) ? $this->options['active_class'] : '';
        } else {
            return isset($this->options['default_class']) ? $this->options['default_class'] : '';
        }
    }
}
