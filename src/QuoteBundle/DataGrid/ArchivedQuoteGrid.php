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

namespace SolidInvoice\QuoteBundle\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use SolidInvoice\CoreBundle\Doctrine\Filter\ArchivableFilter;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;

#[AsDataGrid(name: 'archived_quote_grid', title: 'Archived Quotes')]
final class ArchivedQuoteGrid extends BaseQuoteGrid
{
    #[Override]
    public function actions(): array
    {
        return [];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Activate')
            ->icon('refresh')
            ->color('success')
            ->action(static function (QuoteRepository $repository, array $selectedItems): void {
                $repository->restoreQuotes($selectedItems);
            });
    }

    /**
     * parent::query() adds an `andWhere` restricting the *client* to
     * not-archived; disableForGrid()'s `where()` deliberately resets the
     * whole WHERE clause to its own archived-only predicate rather than
     * appending to it, or this grid would show nothing. Do not change that
     * `where()` to `andWhere()` without re-checking this grid still lists
     * archived quotes. Note this grid does not repeat QuoteGrid's
     * `client IS NOT NULL` guard, so a client-less archived quote stays
     * listed here — unchanged from before this fix.
     */
    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        return ArchivableFilter::disableForGrid($entityManager, parent::query($entityManager, $query));
    }
}
