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

namespace SolidInvoice\InvoiceBundle\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;

#[AsDataGrid(name: self::GRID_NAME, title: 'Recurring Invoices')]
class RecurringInvoiceGrid extends BaseRecurringInvoiceGrid
{
    final public const GRID_NAME = 'recurring_invoice_grid';

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $queryBuilder = $query->getQueryBuilder();
        $queryBuilder->andWhere(sprintf('%s.status != :completedStatus', $query->getRootAlias()))
            ->setParameter('completedStatus', RecurringInvoiceStatus::Complete->value);

        return parent::query($entityManager, $query);
    }
}
