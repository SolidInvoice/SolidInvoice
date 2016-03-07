<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle;

use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Grid\Filters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

interface GridInterface
{
    public function requiresStatus();

    public function fetchData(Request $request, EntityManagerInterface $em);
//    /**
//     * @return \APY\DataGridBundle\Grid\Source\Source
//     */
//    public function getSource();
//
//    /**
//     * @param Filters $filters
//     *
//     * @return Filters
//     */
//    public function getFilters(Filters $filters);
//
//    /**
//     * @param QueryBuilder $queryBuilder
//     * @param string       $searchString
//     *
//     * @return mixed
//     */
//    public function search(QueryBuilder $queryBuilder, $searchString);
//
//    /**
//     * @param Collection $collection
//     *
//     * @return Collection
//     */
//    public function getRowActions(Collection $collection);
//
//    /**
//     * @return array
//     */
//    public function getMassActions();
//
//    /**
//     * Get the template to render for the grid.
//     *
//     * @return string
//     */
//    public function getTemplate();
//
//    /**
//     * @return bool
//     */
//    public function isSearchable();
//
//    /**
//     * @return bool
//     */
//    public function isFilterable();
}
