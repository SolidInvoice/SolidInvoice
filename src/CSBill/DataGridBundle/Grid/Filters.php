<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Grid;

class Filters implements \Iterator
{
    protected $filters = array();

    protected $filterString;

    protected $pointer = 0;

    protected $isFilterActive = false;

    protected $activeFilter;

    public function __construct($filterString)
    {
        $this->filterString = $filterString;
    }

    /**
     * @param string        $name
     * @param null|\Closure $callback
     * @param bool          $default
     * @param array         $options
     *
     * @return $this
     */
    public function add($name, $callback, $default = false, array $options = array())
    {
        $active = $this->filterString === $name ?: $default;

        if ($active && !$default) {
            $this->isFilterActive = true;
            $this->activeFilter = count($this->filters);
        }

        $filter = new Filter($name, $callback, $active, $options);

        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @return Filter
     */
    public function getActiveFilter()
    {
        return $this->filters[$this->activeFilter];
    }

    /**
     * @return bool
     */
    public function isFilterActive()
    {
        return $this->isFilterActive;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->pointer++;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->filters[$this->pointer];
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->filters[$this->pointer]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->pointer = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->pointer;
    }
}
