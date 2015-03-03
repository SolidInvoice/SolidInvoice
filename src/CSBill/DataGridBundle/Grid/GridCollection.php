<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\DataGridBundle\Grid;

use CSBill\DataGridBundle\GridInterface;

class GridCollection
{
    /**
     * @var GridInterface[]
     */
    protected $grids = array();

    /**
     * @var string
     */
    protected $active;

    /**
     * @param GridInterface|string $grid
     * @param string               $name
     * @param string               $icon
     *
     * @return $this
     */
    public function add($grid, $name, $icon = null)
    {
        $this->grids[] = array(
            'name' => strtolower($name),
            'icon' => $icon,
            'grid' => $grid,
        );

        return $this;
    }

    /**
     * @return array
     */
    public function getGrids()
    {
        return $this->grids;
    }

    /**
     * @param string $name
     *
     * @return GridInterface
     * @throws \Exception
     */
    public function getGrid($name = null)
    {
        if (null === $name) {
            reset($this->grids);

            return $this->grids[0];
        }

        foreach ($this->grids as $grid) {
            if ($grid['name'] === strtolower($name)) {
                return $grid;
            }
        }

        throw new \Exception(sprintf('The grid "%s" does not exist', $name));
    }

    /**
     * @return string
     */
    public function getActive()
    {
        if (null === $this->active) {
            return $this->getGrid()['name'];
        }

        return $this->active;
    }

    /**
     * @param string $active
     *
     * @return GridCollection
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }
}