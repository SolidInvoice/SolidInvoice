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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("ALL")
 */
class Grid implements GridInterface
{
    /**
     * @var ArrayCollection
     * @Serializer\Expose()
     */
    private $columns;

    /**
     * @var string
     * @Serializer\Expose()
     */
    private $name;

    /**
     * @var array
     * @Serializer\Exclude()
     */
    private $source;

    /**
     * @var array
     * @Serializer\Expose()
     */
    private $filters;

    /**
     * @param array $gridData
     */
    public function __construct(array $gridData)
    {
	$this->name = $gridData['name'];
	$this->columns = new ArrayCollection(array_values($gridData['columns']));

	foreach ($gridData['filters'] as &$filter) {
	    if (array_key_exists('data', $filter) && $filter['data'] instanceof EntityRepository) {
		$method = $filter['source']['method'];
		$filter['data'] = $filter['data']->$method();

		unset($filter['source']);
            }
        }

	$this->filters = $gridData['filters'];
	$this->source = $gridData['source'];
    }

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @return array
     */
    public function fetchData(EntityManagerInterface $entityManager)
    {
	$method = $this->source['method'];

	return $entityManager->getRepository($this->source['repository'])->$method();
    }

    /**
     * @return bool
     */
    public function requiresStatus()
    {
	$criteria = Criteria::create();
	$criteria->where($criteria->expr()->contains('cell', 'status'));

	return count($this->columns->matching($criteria)) > 0;
    }
}
//
//class Grid extends DataGrid
//{
//    /**
//     * @var bool
//     */
//    public $searchString;
//
//    /**
//     * @var Filters
//     */
//    protected $filters;
//
//    /**
//     * @var GridInterface
//     */
//    private $entity;
//
//    /**
//     * @var GridCollection
//     */
//    private $collection;
//
//    /**
//     * @param GridInterface|string $grid
//     *
//     * @return $this
//     *
//     * @throws \Exception
//     */
//    public function create($grid)
//    {
//        if ($grid instanceof GridCollection) {
//            $this->collection = $grid;
//            $activeGrid = $this->request->query->get('grid');
//
//            $this->collection->setActive($activeGrid);
//            $grid = $this->collection->getGrid($activeGrid)['grid'];
//        }
//
//        if (is_string($grid)) {
//            $grid = $this->container->get($grid);
//        }
//
//        if (!$grid instanceof GridInterface) {
//            throw new \Exception('Grid needs to implement GridInterface');
//        }
//
//        $this->entity = $grid;
//
//        $source = $grid->getSource();
//
//        $this->filters = new Filters($this->request->get('filter'));
//
//        $grid->getFilters($this->filters);
//
//        $searchString = $this->request->get('search');
//
//        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($grid, $searchString) {
//            if ($this->filters->isFilterActive()) {
//                $filter = $this->filters->getActiveFilter();
//                $filter($queryBuilder);
//            }
//
//            if (!empty($searchString)) {
//                $this->searchString = $searchString;
//                $grid->search($queryBuilder, $searchString);
//            }
//        });
//
//        // Attach the source to the grid
//        $this->setSource($source);
//
//        $grid->getRowActions($collection = new Collection());
//
//        if (!$collection->isEmpty()) {
//            $actionsRow = $this->getActionColumn($collection);
//
//            $this->addColumn($actionsRow, 100);
//        }
//
//        $massActions = $grid->getMassActions();
//
//        array_walk($massActions, array($this, 'addMassAction'));
//
//        $this->createHash();
//
//        $requestData = $this->request->get($this->getHash());
//
//        if (
//            1 === count($requestData) &&
//            isset($requestData[self::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED]) &&
//            $requestData[self::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED] === '0'
//        ) {
//            $this->request->request->remove($this->getHash());
//        }
//
//        return $this;
//    }
//
//    /**
//     * @return GridCollection
//     */
//    public function getCollection()
//    {
//        return $this->collection;
//    }
//
//    /**
//     * @param string|array $param1
//     * @param array        $param2
//     * @param Response     $response
//     *
//     * @return Response
//     */
//    public function getGridResponse($param1 = null, $param2 = null, Response $response = null)
//    {
//        if (is_array($param1) || $param1 === null) {
//            $parameters = (array) $param1;
//            $view = $this->entity->getTemplate();
//        } else {
//            $parameters = (array) $param2;
//            $view = $param1;
//        }
//
//        return parent::getGridResponse($view, $parameters, $response);
//    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function deleteAction($ids, $actionAllKeys)
//    {
//        parent::deleteAction($ids, $actionAllKeys);
//
//        /** @var FlashBag $flashBag */
//        $flashBag = $this->session->getBag('flashes');
//
//        $flashBag->add('success', 'Records Successfully Deleted');
//    }
//
//    /**
//     * @return bool
//     */
//    public function isFiltered()
//    {
//        return $this->filters->isFilterActive() || null !== $this->searchString || parent::isFiltered();
//    }
//
//    /**
//     * @return bool
//     */
//    public function searchActive()
//    {
//        return null !== $this->searchString;
//    }
//
//    /**
//     * @param Collection $collection
//     *
//     * @return ActionsColumn
//     */
//    protected function getActionColumn(Collection $collection)
//    {
//        $columns = array();
//
//        /** @var ActionColumn $action */
//        foreach ($collection as $action) {
//            $actionColumn = new RowAction($action->getTitle(), $action->getRoute());
//            $actionColumn->setIcon($action->getIcon());
//            $actionColumn->setClass($action->getClass());
//            $actionColumn->addAttribute('rel', 'tooltip');
//            $actionColumn->addAttribute('title', $action->getTitle());
//            $actionColumn->manipulateRender($action->getCallback());
//
//            $confirm = $action->getConfirm();
//            if (!empty($confirm)) {
//                $actionColumn->addAttribute('data-confirm', $confirm);
//            }
//
//            $routeParameters = $action->getRouteParams();
//            if (!empty($routeParameters)) {
//                $actionColumn->addRouteParameters($routeParameters);
//            }
//
//            foreach ($action->getAttributes() as $name => $value) {
//                $actionColumn->addAttribute($name, $value);
//            }
//
//            $columns[] = $actionColumn;
//        }
//
//        return new ActionsColumn('actions', 'Action', $columns);
//    }
//
//    /**
//     * @return bool
//     */
//    public function isFilterable()
//    {
//        if (null === $this->entity) {
//            return false;
//        }
//
//        return $this->entity->isFilterable();
//    }
//
//    /**
//     * @return bool
//     */
//    public function isSearchable()
//    {
//        if (null === $this->entity) {
//            return false;
//        }
//
//        return $this->entity->isSearchable();
//    }
//
//    /**
//     * @return Filters
//     */
//    public function getFilters()
//    {
//        return $this->filters;
//    }
//
//    /**
//     * @return array
//     */
//    public function getRouteParameters()
//    {
//        if ($this->request->query->has('grid')) {
//            $this->routeParameters['grid'] = $this->request->query->get('grid');
//        }
//
//        return parent::getRouteParameters();
//    }
//}
