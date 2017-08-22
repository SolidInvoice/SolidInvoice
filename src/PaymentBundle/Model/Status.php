<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Model;

class Status
{
    const STATUS_UNKNOWN = 'unknown';
    const STATUS_FAILED = 'failed';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NEW = 'new';
    const STATUS_CAPTURED = 'captured';
    const STATUS_AUTHORIZED = 'authorized';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CREDIT = 'credit';
}
