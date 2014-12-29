<?php

namespace CSBill\DataGridBundle;

use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Grid as DataGrid;
use CSBill\DataGridBundle\Action\ActionColumn;
use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Action\RowAction;
use CSBill\DataGridBundle\Action\MassAction;
use CSBill\DataGridBundle\Grid\Filters;
use Doctrine\ORM\QueryBuilder;

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
     * @param GridInterface $grid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function create(GridInterface $grid)
    {
        $source = $grid->getSource();

        $this->filters = new Filters($this->request->get('filter'));

        $grid->getFilters($this->filters);

        $searchString = $this->request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($grid, $searchString) {
            if ($grid->isFilterable() && $this->filters->isFilterActive()) {
                $filter = $this->filters->getActiveFilter();
                $filter($queryBuilder);
            }

            if ($grid->isSearchable() && !empty($searchString)) {
                $this->searchString = $searchString;
                $grid->search($queryBuilder, $searchString);
            }
        });

        // Attach the source to the grid
        $this->setSource($source);

        $collection = new Collection();

        $grid->getActions($collection);

        $actionsRow = $this->getActionColumn($collection);

        $this->addColumn($actionsRow, 100);

        // TODO: Move this to each individual grids
        $massAction = new MassAction('Archive', function(){
            var_dump(func_get_args());
            exit;
        }, true);

        $massAction->setIcon('archive');
        $massAction->setClass('warning');

        $this->addMassAction($massAction);

        $this->createHash();

        $requestData = $this->request->get($this->getHash());

        if (1 === count($requestData) && isset($requestData[\APY\DataGridBundle\Grid\Grid::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED]) && $requestData[\APY\DataGridBundle\Grid\Grid::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED] === '0') {} {
            $this->request->request->remove($this->getHash());
        }

        // TODO: This needs to be handled per grid
        return $this->getGridResponse(
            'CSBillClientBundle:Default:index.html.twig'
        );
    }

    /**
     * @return bool
     */
    public function isFiltered()
    {
        return $this->filters->isFilterActive() || null !== $this->searchString || parent::isFiltered();
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
     * @return Filters
     */
    public function getFilters()
    {
        return $this->filters;
    }
}