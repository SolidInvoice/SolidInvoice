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
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly string $repository,
        private readonly string $method
    ) {
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
