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

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Draft => 'secondary',
            self::Pending => 'yellow',
            self::Accepted => 'green',
            self::Cancelled => 'teal',
            self::Declined => 'red',
            self::Archived => 'purple',
        };
    }
}
