<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Action;

class ActionColumn
{
    /**
     * @var string
     */
    protected $route;

    /**
     * @var string
     */
    protected $icon;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $routeParams;

    /**
     * @var string
     */
    protected $confirm;

    /**
     * @var string
     */
    protected $class = 'default';

    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $route
     *
     * @param array  $routeParams
     *
     * @return $this
     */
    public function setRoute($route, array $routeParams = array())
    {
        $this->route = $route;

        if (!empty($routeParams)) {
            $this->setRouteParams($routeParams);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * @param string $routeParams
     *
     * @return $this
     */
    public function setRouteParams($routeParams)
    {
        $this->routeParams = $routeParams;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * @param string $confirm
     *
     * @return $this
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
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

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param callable $callback
     *
     * @return ActionColumn
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }
}