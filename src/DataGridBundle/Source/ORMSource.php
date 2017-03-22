<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Source;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class ORMSource implements SourceInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $repository;

    /**
     * @var string
     */
    private $method;

    /**
     * ORMSource constructor.
     *
     * @param ManagerRegistry $registry
     * @param string          $repository
     * @param string          $method
     */
    public function __construct(ManagerRegistry $registry, string $repository, string $method)
    {
        $this->registry = $registry;
        $this->repository = $repository;
        $this->method = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(array $parameters = []): QueryBuilder
    {
        $repository = $this->registry->getRepository($this->repository);

        $method = $this->method;
        $qb = $repository->{$method}($parameters);

        if (!$qb instanceof QueryBuilder) {
            throw new \Exception('Grid source should return a query builder');
        }

        return $qb;
    }
}
