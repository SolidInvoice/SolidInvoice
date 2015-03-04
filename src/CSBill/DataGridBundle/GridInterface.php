<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
    public function getRowActions(Collection $collection);

    /**
     * @return array
     */
    public function getMassActions();

    /**
     * Get the template to render for the grid
     *
     * @return string
     */
    public function getTemplate();

    /**
     * @return bool
     */
    public function isSearchable();

    /**
     * @return bool
     */
    public function isFilterable();
}