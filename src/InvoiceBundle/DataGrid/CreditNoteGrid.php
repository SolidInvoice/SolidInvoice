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

use Brick\Math\BigNumber;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Override;
use SolidInvoice\CoreBundle\Doctrine\Filter\ArchivableFilter;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use Symfony\Component\Translation\TranslatableMessage;

#[AsDataGrid(name: 'credit_note_grid', title: 'Credit Notes')]
final class CreditNoteGrid extends Grid
{
    #[Override]
    public function entityFQCN(): string
    {
        return CreditNote::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('creditNoteId')
                ->label('Credit Note #'),
            RelativeDateColumn::new('creditNoteDate')
                ->format('d F Y')
                ->filter(new DateRangeFilter('creditNoteDate')),
            StringColumn::new('client')
                ->searchable(false)
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            StringColumn::new('invoice')
                ->label('Invoice')
                ->searchable(false),
            StringColumn::new('status')
                ->twigFunction('credit_note_label')
                ->filter(ChoiceFilter::new('status', array_column(array_map(static fn (CreditNoteStatus $s) => [$s->value, $s->getLabel()], CreditNoteStatus::cases()), 1, 0))->multiple()),
            MoneyColumn::new('total')
                ->formatValue(fn (BigNumber $value, CreditNote $creditNote) => new Money((string) $value, $creditNote->getCurrency())),
        ];
    }

    /**
     * @return Action[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            ViewAction::new('_credit_notes_view', ['id' => 'id']),
        ];
    }

    /**
     * Joins the client eagerly so the client column never touches a lazy reference —
     * mirroring {@see BaseInvoiceGrid::query()}. A left join is safe for both `client`
     * (`nullable: false`) and `invoice` (nullable, standalone credit notes have none).
     */
    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query->getQueryBuilder()
            ->select(ORMSource::ALIAS, 'client', 'invoice')
            ->leftJoin(ORMSource::ALIAS . '.client', 'client')
            ->leftJoin(ORMSource::ALIAS . '.invoice', 'invoice')
            ->orderBy(ORMSource::ALIAS . '.creditNoteDate', 'DESC');

        return ArchivableFilter::suspendForJoinedAssociations($entityManager, $query);
    }

    public function getCreateRoute(): ?string
    {
        return '_credit_notes_create';
    }

    #[Override]
    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('credit_note.menu.create');
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.credit_note.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.credit_note.description');
    }
}
