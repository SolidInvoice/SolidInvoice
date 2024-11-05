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

namespace SolidInvoice\DataGridBundle;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Query;

interface GridInterface
{
    /**
     * @return class-string
     */
    public function entityFQCN(): string;

    /**
     * @return list<Column>
     */
    public function columns(): array;

    /**
     * @return list<Action>
     */
    public function actions(): array;

    /**
     * @return iterable<BatchAction>
     */
    public function batchActions(): iterable;

    /**
     * @return iterable<string, ColumnFilterInterface|null>
     */
    public function filters(): iterable;

    public function query(EntityManagerInterface $entityManager, Query $query): Query;
}
