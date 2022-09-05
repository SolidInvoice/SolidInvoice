<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Source;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\Source\ORMSourceTest
 */
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

    public function __construct(ManagerRegistry $registry, string $repository, string $method)
    {
        $this->registry = $registry;
        $this->repository = $repository;
        $this->method = $method;
    }

    public function fetch(array $parameters = []): QueryBuilder
    {
        $repository = $this->registry->getRepository($this->repository);

        $method = $this->method;
        $qb = $repository->{$method}($parameters);

        if (! $qb instanceof QueryBuilder) {
            throw new Exception('Grid source should return a query builder');
        }

        return $qb;
    }
}
