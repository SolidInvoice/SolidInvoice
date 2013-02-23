<?php

namespace CSBill\DataGridBundle\Grid;

use Symfony\Component\HttpFoundation\Request;
//use Zend\StdLib\CallbackHandler;

class Filters implements \Iterator
{
    protected $filters = array();

    protected $request;

    protected $pointer = 0;

    protected $isFilterActive = false;

    protected $activeFilter;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function add($name, $callback, $default = false, array $options = array())
    {
        $active = $default;

        if (($filter = $this->request->get('filter')) !== null) {
            $active = $filter === $name;
        }

        if ($active && !$default) {
            $this->isFilterActive = true;
            $this->activeFilter = count($this->filters);
        }

        $filter = new Filter($name, $callback, $active, $options);

        $this->filters[] = $filter;

        return $this;
    }

    public function getActiveFilter()
    {
        return $this->filters[$this->activeFilter];
    }

    public function isFilterActive()
    {
        return $this->isFilterActive;
    }

    public function next()
    {
        $this->pointer++;
    }

    public function current()
    {
        return $this->filters[$this->pointer];
    }

    public function valid()
    {
        return isset($this->filters[$this->pointer]);
    }

    public function rewind()
    {
        $this->pointer = 0;
    }

    public function key()
    {
        return $this->pointer;
    }
}
