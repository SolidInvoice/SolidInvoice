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
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Translation\TranslatableMessage;
use function array_key_exists;

#[AsDataGrid(name: 'quote_grid', title: 'Active Quotes')]
final class QuoteGrid extends BaseQuoteGrid
{
    #[Override]
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

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        // The client join, select and archivable-filter suspension live in
        // BaseQuoteGrid::query() so ArchivedQuoteGrid inherits them too.
        $query = parent::query($entityManager, $query);

        // BaseQuoteGrid's join is a left join (Quote::$client is nullable);
        // this keeps the active grid's previous inner-join behaviour of
        // excluding a client-less quote.
        $query->getQueryBuilder()->andWhere('client.id IS NOT NULL');

        if (array_key_exists('client_id', $this->context)) {
            $query
                ->getQueryBuilder()
                ->andWhere(ORMSource::ALIAS . '.client = :client_id')
                ->setParameter('client_id', $this->context['client_id'], UlidType::NAME);
        }

        return $query;
    }

    public function getCreateRoute(): ?string
    {
        return '_quotes_create';
    }

    #[Override]
    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('Create Quote');
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.quote.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.quote.description');
    }
}
