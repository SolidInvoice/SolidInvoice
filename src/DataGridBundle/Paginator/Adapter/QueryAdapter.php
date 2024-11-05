<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Paginator\Adapter;

use Closure;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter as BaseQueryAdapter;

/**
 * @extends BaseQueryAdapter<QueryBuilder|Query>
 */
final class QueryAdapter extends BaseQueryAdapter
{
    public function __construct(
        Query|QueryBuilder $query,
        bool $fetchJoinCollection = true,
        ?bool $useOutputWalkers = null,
        private readonly ?Closure $beforeQuery = null,
        private readonly ?Closure $afterQuery = null,
    ) {
        parent::__construct($query, $fetchJoinCollection, $useOutputWalkers);
    }

    /**
     * @phpstan-return int<0, max>
     */
    public function getNbResults(): int
    {
        try {
            ($this->beforeQuery ?? static fn () => null)();
            return parent::getNbResults();
        } finally {
            ($this->afterQuery ?? static fn () => null)();
        }
    }

    public function getSlice(int $offset, int $length): iterable
    {
        try {
            ($this->beforeQuery ?? static fn () => null)();
            return parent::getSlice($offset, $length);
        } finally {
            ($this->afterQuery ?? static fn () => null)();
        }
    }
}
