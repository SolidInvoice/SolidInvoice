<?php

namespace CSBill\DataGridBundle;

use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Grid\Filters;
use Doctrine\ORM\QueryBuilder;

interface GridInterface
{
    /**
     * @return \APY\DataGridBundle\Grid\Source\Source
     */
    public function getSource();

    /**
     * @param Filters $filters
     *
     * @return Filters
     */
    public function getFilters(Filters $filters);

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $searchString
     *
     * @return mixed
     */
    public function search(QueryBuilder $queryBuilder, $searchString);

    /**
     * @param Collection $collection
     *
     * @return Collection
     */
    public function getActions(Collection $collection);

    /**
     * @return bool
     */
    public function isSearchable();

    /**
     * @return bool
     */
    public function isFilterable();
}