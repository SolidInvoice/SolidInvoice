<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\DataGridBundle;

use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Grid\Filters;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractGrid implements GridInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function getSource();

    /**
     * {@inheritdoc}
     */
    public function getFilters(Filters $filters)
    {
        return $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function search(QueryBuilder $queryBuilder, $searchString)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRowActions(Collection $collection)
    {
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'CSBillDataGridBundle:Grid:default.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable()
    {
        return false;
    }
}