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

use Brick\Math\BigNumber;
use Money\Money;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;

#[AsDataGrid(name: 'quote_grid')]
final class QuoteGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Quote::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('quoteId')
                ->label('Quote #'),
            StringColumn::new('client'),

            MoneyColumn::new('total')
                ->formatValue(fn (BigNumber $value, Quote $quote) => new Money((string) $value, $quote->getClient()?->getCurrency())),

            StringColumn::new('status')
                ->twigFunction('quote_label')
                ->filter(ChoiceFilter::new('status', Graph::statusArray())->multiple()),
            MoneyColumn::new('tax')
                ->formatValue(fn (BigNumber $value, Quote $quote) => new Money((string) $value, $quote->getClient()?->getCurrency())),
            MoneyColumn::new('discount.value')
                ->label('Discount')
                ->formatValue(fn (float|BigNumber $value, Quote $quote) => new Money((string) $value, $quote->getClient()?->getCurrency())),
            DateTimeColumn::new('created')
                ->format('d F Y')
                ->filter(new DateRangeFilter('created'))
        ];
    }

    public function actions(): array
    {
        return [
            ViewAction::new('_quotes_view', ['id' => 'id']),
            EditAction::new('_quotes_edit', ['id' => 'id']),
        ];
    }
}
