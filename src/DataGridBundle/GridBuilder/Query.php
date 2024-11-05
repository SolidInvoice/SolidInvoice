<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder;

use Closure;
use Doctrine\ORM\QueryBuilder;

final class Query
{
    public const BEFORE_QUERY = 'beforeQuery';

    public const AFTER_QUERY = 'afterQuery';

    /**
     * @var array<string, Closure>
     */
    private array $callbacks = [];

    public function __construct(
        private QueryBuilder $builder,
        private string $rootAlias
    ) {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    public function setQueryBuilder(QueryBuilder $builder): self
    {
        $this->builder = $builder;
        $this->setRootAlias(current($builder->getRootAliases()));

        return $this;
    }

    public function getRootAlias(): string
    {
        return $this->rootAlias;
    }

    public function setRootAlias(string $rootAlias): self
    {
        $this->rootAlias = $rootAlias;

        return $this;
    }

    public function beforeQuery(Closure $callback): self
    {
        $this->callbacks[self::BEFORE_QUERY] = $callback;

        return $this;
    }

    public function afterQuery(Closure $callback): self
    {
        $this->callbacks[self::AFTER_QUERY] = $callback;

        return $this;
    }

    /**
     * @return array<string, Closure>
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    public function getCallback(string $type): ?Closure
    {
        return $this->callbacks[$type] ?? null;
    }
}
