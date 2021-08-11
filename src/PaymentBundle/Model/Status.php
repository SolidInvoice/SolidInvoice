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

class Status
{
    public const STATUS_UNKNOWN = 'unknown';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NEW = 'new';

    public const STATUS_CAPTURED = 'captured';

    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CREDIT = 'credit';
}
