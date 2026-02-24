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
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;

#[AsDataGrid(name: 'completed_recurring_invoice_grid', title: 'Completed Recurring Invoices')]
final class CompletedRecurringInvoiceGrid extends BaseRecurringInvoiceGrid
{
    // Completed invoices don't need the nextRunDate column
    // so we use the parent implementation which includes it

    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Reactivate')
            ->icon('refresh')
            ->color('success')
            ->action(static function (RecurringInvoiceRepository $repository, EntityManagerInterface $em, array $selectedItems): void {
                $invoices = $repository->findBy(['id' => $selectedItems]);
                foreach ($invoices as $invoice) {
                    $invoice->setStatus(RecurringInvoiceStatus::Active);
                    $em->persist($invoice);
                }
                $em->flush();
            });
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $queryBuilder = $query->getQueryBuilder();
        $queryBuilder->andWhere(sprintf('%s.status = :completedStatus', $query->getRootAlias()))
            ->setParameter('completedStatus', RecurringInvoiceStatus::Complete->value);

        return parent::query($entityManager, $query);
    }

    public function getCreateRoute(): ?string
    {
        return null;
    }
}
