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

namespace SolidInvoice\ProductBundle\DataGrid;

use Brick\Math\BigDecimal;
use Locale;
use NumberFormatter;
use Override;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\EntityFilter;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Translation\TranslatableMessage;
use function mb_strimwidth;

/**
 * @see \SolidInvoice\ProductBundle\Tests\DataGrid\ProductGridTest
 */
#[AsDataGrid(name: 'product_grid', title: 'Products')]
final class ProductGrid extends Grid
{
    private const int DESCRIPTION_LENGTH = 80;

    public function __construct(
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function entityFQCN(): string
    {
        return Product::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('name')
                ->label('label.name')
                ->linkToRoute('_products_edit', ['id' => 'id']),
            StringColumn::new('description')
                ->label('label.description')
                ->formatValue(static fn (?string $value): string => mb_strimwidth($value ?? '', 0, self::DESCRIPTION_LENGTH, '…')),
            StringColumn::new('price')
                ->label('product.grid.price')
                ->sortable()
                ->cellClass('col-money')
                ->formatValue(static fn (mixed $value): string => self::formatNumber($value)),
            StringColumn::new('quantity')
                ->label('product.grid.quantity')
                ->formatValue(static fn (mixed $value): string => self::formatNumber($value)),
            StringColumn::new('tax')
                ->label('product.grid.tax')
                ->formatValue(static fn (?Tax $value): string => $value?->getName() ?? '—')
                ->filter(EntityFilter::new(Tax::class, 'tax', 'name')->multiple()),
            DateTimeColumn::new('created')
                ->label('label.created')
                ->format('d F Y'),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        return [
            BatchAction::new('action.delete')
                ->icon('trash')
                ->color('danger')
                ->confirmMessage('product.grid.delete_confirm')
                // The grid has no hook to deny the whole list, so the destructive action checks the gate itself.
                ->action(function (ProductRepository $repository, array $selectedItems): void {
                    if (! $this->featureGate->isEnabled(Feature::Products->value)) {
                        return;
                    }

                    $repository->deleteProducts($selectedItems);
                }),
        ];
    }

    /**
     * @return EditAction[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            EditAction::new('_products_edit', ['id' => 'id']),
        ];
    }

    public function getCreateRoute(): string
    {
        return '_products_add';
    }

    #[Override]
    public function getCreateLabel(): TranslatableMessage
    {
        return new TranslatableMessage('product.grid.create');
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.product.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.product.description');
    }

    /**
     * A plain locale-formatted number: product prices carry no currency.
     */
    private static function formatNumber(mixed $value): string
    {
        $formatter = new NumberFormatter(Locale::getDefault(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 6);

        return (string) $formatter->format((float) ($value instanceof BigDecimal ? (string) $value : $value));
    }
}
