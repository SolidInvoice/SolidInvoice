<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\DataGrid;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use Symfony\Component\Translation\TranslatableMessage;

#[AsDataGrid(name: 'client_grid', title: 'Clients')]
final class ClientGrid extends BaseClientGrid
{
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Archive')
            ->icon('trash')
            ->color('warning')
            ->action(static function (ClientRepository $repository, array $selectedItems): void {
                $repository->archiveClients($selectedItems);
            });
    }

    public function getCreateRoute(): ?string
    {
        return '_clients_add';
    }

    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('Create Client');
    }
}
