<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Grid;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\CurrencyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\UrlColumn;

#[AsDataGrid(name: 'client_grid')]
final class ClientGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Client::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('name'),
            UrlColumn::new('website'),
            CurrencyColumn::new('currencyCode')->label('Currency'),
            StringColumn::new('status')->twigFunction('client_label'),
            DateTimeColumn::new('created')->format('d F Y'),
        ];
    }

    public function actions(): array
    {
        return [
            ViewAction::new('_clients_view', ['id' => 'id']),
            EditAction::new('_clients_edit', ['id' => 'id']),
        ];
    }

    public function batchActions(): array
    {
        return [
            BatchAction::new('Delete')
                ->icon('trash')
                ->color('danger')
                ->action(static function (ClientRepository $repository, array $selectedItems): void {
                    $repository->deleteClients($selectedItems);
                }),
            BatchAction::new('Archive')
                ->icon('trash')
                ->color('warning')
                ->action(static function (ClientRepository $repository, array $selectedItems): void {
                    $repository->archiveClients($selectedItems);
                }),
        ];
    }
}
