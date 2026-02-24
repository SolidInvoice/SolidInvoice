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

enum PaymentStatus: string
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
}
