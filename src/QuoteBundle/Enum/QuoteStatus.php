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

namespace SolidInvoice\QuoteBundle\Enum;

use SolidInvoice\CoreBundle\Enum\HasStatusLabel;
use SolidInvoice\CoreBundle\Enum\StatusVariant;

enum QuoteStatus: string implements HasStatusLabel
{
    case New = 'new';
    case Draft = 'draft';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Cancelled = 'cancelled';
    case Declined = 'declined';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Cancelled => 'Cancelled',
            self::Declined => 'Declined',
            self::Archived => 'Archived',
        };
    }

    public function getVariant(): StatusVariant
    {
        return match ($this) {
            self::New => StatusVariant::Neutral,
            self::Draft => StatusVariant::Neutral,
            self::Pending => StatusVariant::Warning,
            self::Accepted => StatusVariant::Success,
            self::Cancelled => StatusVariant::Neutral,
            self::Declined => StatusVariant::Danger,
            self::Archived => StatusVariant::Neutral,
        };
    }
}
