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
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return 'status.' . $this->value;
    }

    public function getVariant(): StatusVariant
    {
        return match ($this) {
            self::New, self::Draft, self::Archived, self::Cancelled => StatusVariant::Neutral,
            self::Pending => StatusVariant::Warning,
            self::Paid => StatusVariant::Success,
            self::Overdue => StatusVariant::Danger,
        };
    }
}
