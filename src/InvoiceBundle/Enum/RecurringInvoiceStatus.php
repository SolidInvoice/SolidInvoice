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

enum RecurringInvoiceStatus: string implements HasStatusLabel
{
    case New = 'new';
    case Active = 'active';
    case Complete = 'complete';
    case Draft = 'draft';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Active => 'Active',
            self::Complete => 'Complete',
            self::Draft => 'Draft',
            self::Paused => 'Paused',
            self::Cancelled => 'Cancelled',
            self::Archived => 'Archived',
        };
    }

    public function getVariant(): StatusVariant
    {
        return match ($this) {
            self::New => StatusVariant::Neutral,
            self::Active => StatusVariant::Info,
            // Complete is a resting, reversible state. The grid can restart it back to
            // Active, and nothing is owed, so it does not take the money green.
            self::Complete => StatusVariant::Neutral,
            self::Draft => StatusVariant::Neutral,
            self::Paused => StatusVariant::Warning,
            self::Cancelled => StatusVariant::Neutral,
            self::Archived => StatusVariant::Neutral,
        };
    }
}
