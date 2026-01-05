<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\DataGrid;

use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;

#[AsDataGrid(name: self::GRID_NAME, title: 'Recurring Invoices')]
class RecurringInvoiceGrid extends BaseRecurringInvoiceGrid
{
    final public const GRID_NAME = 'recurring_invoice_grid';
}
