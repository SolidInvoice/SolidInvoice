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

namespace SolidInvoice\PaymentBundle\Model;

// @TODO: Convert to Enum
class Status
{
    final public const STATUS_UNKNOWN = 'unknown';

    final public const STATUS_FAILED = 'failed';

    final public const STATUS_SUSPENDED = 'suspended';

    final public const STATUS_EXPIRED = 'expired';

    final public const STATUS_PENDING = 'pending';

    final public const STATUS_CANCELLED = 'cancelled';

    final public const STATUS_NEW = 'new';

    final public const STATUS_CAPTURED = 'captured';

    final public const STATUS_AUTHORIZED = 'authorized';

    final public const STATUS_REFUNDED = 'refunded';

    final public const STATUS_CREDIT = 'credit';

    /**
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return [
            self::STATUS_UNKNOWN => 'Unknown',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_NEW => 'New',
            self::STATUS_CAPTURED => 'Captured',
            self::STATUS_AUTHORIZED => 'Authorized',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_CREDIT => 'Credit',
        ];
    }
}
