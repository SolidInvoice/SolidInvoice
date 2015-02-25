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

use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Grid as DataGrid;
use CSBill\DataGridBundle\Action\ActionColumn;
use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Action\RowAction;
use CSBill\DataGridBundle\Grid\Filters;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

class Grid extends DataGrid
{
    /**
     * @var bool
     */
    public $searchString;

    /**
     * @var Filters
     */
    protected $filters;

    /**
     * @var GridInterface
     */
    private $entity;

    /**
     * @param GridInterface $grid
     *
     * @return $this
     * @throws \Exception
     */
    public function create($grid)
    {
        if (is_string($grid)) {
            $grid = $this->container->get($grid);
        }

        if (!$grid instanceof GridInterface) {
            throw new \Exception('Grid needs to implement GridInterface');
        }

        $this->entity = $grid;

        $source = $grid->getSource();

        $this->filters = new Filters($this->request->get('filter'));

        $grid->getFilters($this->filters);

        $searchString = $this->request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($grid, $searchString) {
            if ($this->filters->isFilterActive()) {
                $filter = $this->filters->getActiveFilter();
                $filter($queryBuilder);
            }

            if (!empty($searchString)) {
                $this->searchString = $searchString;
                $grid->search($queryBuilder, $searchString);
            }
        });

        // Attach the source to the grid
        $this->setSource($source);

        $collection = new Collection();

        $grid->getRowActions($collection);

        $actionsRow = $this->getActionColumn($collection);

        $this->addColumn($actionsRow, 100);

        $massActions = $grid->getMassActions();

        array_walk($massActions, array($this, 'addMassAction'));

        $this->createHash();

        $requestData = $this->request->get($this->getHash());

        if (
            1 === count($requestData) &&
            isset($requestData[Grid::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED]) &&
            $requestData[Grid::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED] === '0'
        ) {
            $this->request->request->remove($this->getHash());
        }

        return $this;
    }

    /**
     * @param string|array $param1
     * @param array        $param2
     * @param Response     $response
     *
     * @return Response
     *
     */
    public function getGridResponse($param1 = null, $param2 = null, Response $response = null)
    {
        if (is_array($param1) || $param1 === null) {
            $parameters = (array) $param1;
            $view = $this->entity->getTemplate();
        } else {
            $parameters = (array) $param2;
            $view = $param1;
        }

        return parent::getGridResponse($view, $parameters, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAction($ids, $actionAllKeys)
    {
        parent::deleteAction($ids, $actionAllKeys);

        /** @var FlashBag $flashBag */
        $flashBag = $this->session->getBag('flashes');

        $flashBag->add('success', 'Records Successfully Deleted');
    }

    /**
     * @return bool
     */
    public function isFiltered()
    {
        return $this->filters->isFilterActive() || null !== $this->searchString || parent::isFiltered();
    }

    /**
     * @return bool
     */
    public function searchActive()
    {
        return null !== $this->searchString;
    }

    /**
     * @param Collection $collection
     *
     * @return ActionsColumn
     */
    protected function getActionColumn(Collection $collection)
    {
        $columns = array();

        /** @var ActionColumn $action */
        foreach ($collection as $action) {
            $actionColumn = new RowAction($action->getTitle(), $action->getRoute());
            $actionColumn->setIcon($action->getIcon());
            $actionColumn->setClass($action->getClass());
            $actionColumn->addAttribute('rel', 'tooltip');
            $actionColumn->addAttribute('title', $action->getTitle());

            $confirm = $action->getConfirm();
            if (!empty($confirm)) {
                $actionColumn->addAttribute('data-confirm', $confirm);
            }

            $routeParameters = $action->getRouteParams();
            if (!empty($routeParameters)) {
                $actionColumn->addRouteParameters($routeParameters);
            }

            foreach ($action->getAttributes() as $name => $value) {
                $actionColumn->addAttribute($name, $value);
            }

            $columns[] = $actionColumn;
        }

        return new ActionsColumn('actions', 'Action', $columns);
    }

    /**
     * @return bool
     */
    public function isFilterable()
    {
        if (null === $this->entity) {
            return false;
        }

        return $this->entity->isFilterable();
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        if (null === $this->entity) {
            return false;
        }

        return $this->entity->isSearchable();
    }

    /**
     * @return Filters
     */
    public function getFilters()
    {
        return $this->filters;
    }
}