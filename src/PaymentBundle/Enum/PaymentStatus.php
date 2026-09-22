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
use SolidInvoice\CoreBundle\Enum\StatusVariant;

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

    public function getVariant(): StatusVariant
    {
        return match ($this) {
            // The gateway state could not be read, so the money is unaccounted for.
            // Neutral would hide the row, and nothing has actually failed.
            self::Unknown => StatusVariant::Warning,
            self::Failed => StatusVariant::Danger,
            self::Suspended => StatusVariant::Warning,
            self::Expired => StatusVariant::Danger,
            self::Pending => StatusVariant::Warning,
            self::Cancelled => StatusVariant::Neutral,
            self::New => StatusVariant::Info,
            self::Captured => StatusVariant::Success,
            self::Authorized => StatusVariant::Info,
            // A refund drops out of the captured total while the invoice stays Paid.
            // This chip is the only on-screen sign that the money went back.
            self::Refunded => StatusVariant::Warning,
            self::Credit => StatusVariant::Info,
        };
    }
}
