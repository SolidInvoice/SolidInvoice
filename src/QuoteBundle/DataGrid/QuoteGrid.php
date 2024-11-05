<?php

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
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use function array_key_exists;

#[AsDataGrid(name: 'quote_grid', title: 'Active Quotes')]
final class QuoteGrid extends BaseQuoteGrid
{
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Archive')
            ->icon('trash')
            ->color('warning')
            ->action(static function (QuoteRepository $repository, array $selectedItems): void {
                $repository->archiveQuotes($selectedItems);
            });
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query = parent::query($entityManager, $query);

        if (array_key_exists('client_id', $this->context)) {
            $query
                ->getQueryBuilder()
                ->where(ORMSource::ALIAS . '.client = :client_id')
                ->setParameter('client_id', $this->context['client_id'], UuidBinaryOrderedTimeType::NAME);
        }

        return $query;
    }
}
