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

namespace SolidInvoice\InvoiceBundle\Enum;

enum InvoiceStatus: string
{
    case New = 'new';
    case Draft = 'draft';
    case Pending = 'pending';
    case Paid = 'paid';
    case Active = 'active';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Archived = 'archived';
}
