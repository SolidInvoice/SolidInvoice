<?php

namespace CSBill\DataGridBundle\Action;

use APY\DataGridBundle\Grid\Action\RowAction as BaseAction;

class RowAction extends BaseAction
{
    /**
     * @var string
     */
    protected $icon;

    /**
     * @var string
     */
    protected $class = 'default';

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }
}