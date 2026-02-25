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

namespace SolidInvoice\PaymentBundle\Enum;

use SolidInvoice\CoreBundle\Enum\HasStatusLabel;

enum PaymentStatus: string implements HasStatusLabel
{
    case Unknown = 'unknown';
    case Failed = 'failed';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case New = 'new';
    case Captured = 'captured';
    case Authorized = 'authorized';
    case Refunded = 'refunded';
    case Credit = 'credit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => 'Unknown',
            self::Failed => 'Failed',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::Pending => 'Pending',
            self::Cancelled => 'Cancelled',
            self::New => 'New',
            self::Captured => 'Captured',
            self::Authorized => 'Authorized',
            self::Refunded => 'Refunded',
            self::Credit => 'Credit',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unknown => 'primary',
            self::Failed => 'red',
            self::Suspended => 'dark',
            self::Expired => 'purple',
            self::Pending => 'yellow',
            self::Cancelled => 'indigo',
            self::New => 'blue',
            self::Captured => 'green',
            self::Authorized => 'cyan',
            self::Refunded => 'pink',
            self::Credit => 'azure',
        };
    }
}
