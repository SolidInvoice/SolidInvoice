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
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

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
     * @param Request                $request
     * @param EntityManagerInterface $entityManager
     *
     * @return array
     * @throws \Exception
     */
    public function fetchData(Request $request, EntityManagerInterface $entityManager)
    {
	$method = $this->source['method'];

	$queryBuilder = $entityManager->getRepository($this->source['repository'])->$method();

	if (!$queryBuilder instanceof QueryBuilder) {
	    throw new \Exception('Grid should return a query builder');
	}

	$queryBuilder->setMaxResults($request->query->get('per_page'));
	$queryBuilder->setFirstResult(($request->query->get('page') - 1) * $request->query->get('per_page'));

	if ($request->query->has('sort')) {
	    $queryBuilder->orderBy($queryBuilder->getRootAliases()[0].'.'.$request->query->get('sort'), $request->query->get('order'));
	}

	if ($request->query->has('q')) {
	    $queryBuilder->where($queryBuilder->getRootAliases()[0].'.name LIKE :q');
	    $queryBuilder->setParameter('q', '%'.$request->query->get('q').'%');
	}

	$paginator = new Paginator($queryBuilder);

	return [
	    'count' => count($paginator),
	    'items' => $paginator->getQuery()->getArrayResult()
	];
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