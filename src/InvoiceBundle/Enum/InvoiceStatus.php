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

use SolidInvoice\CoreBundle\Enum\HasStatusLabel;
use SolidInvoice\CoreBundle\Enum\StatusVariant;

enum InvoiceStatus: string implements HasStatusLabel
{
    case New = 'new';
    case Draft = 'draft';
    case Pending = 'pending';
    case Paid = 'paid';
    case Active = 'active';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Active => 'Active',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
            self::Archived => 'Archived',
        };
    }

    public function getVariant(): StatusVariant
    {
        return match ($this) {
            self::New => StatusVariant::Neutral,
            self::Draft => StatusVariant::Neutral,
            self::Pending => StatusVariant::Warning,
            self::Paid => StatusVariant::Success,
            // Active is an issued, awaiting-payment place that only legacy rows reach.
            // It means the same as Pending, so it carries the same variant.
            self::Active => StatusVariant::Warning,
            self::Overdue => StatusVariant::Danger,
            self::Cancelled => StatusVariant::Neutral,
            self::Archived => StatusVariant::Neutral,
        };
    }
}
