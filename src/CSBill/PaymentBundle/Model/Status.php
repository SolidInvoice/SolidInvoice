<?php
/**
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\PaymentBundle\Model;

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
}
