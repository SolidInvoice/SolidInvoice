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

namespace SolidInvoice\InvoiceBundle\Model;

final class Graph
{
    public const TRANSITION_ACCEPT = 'accept';

    public const TRANSITION_ACTIVATE = 'activate';

    public const TRANSITION_NEW = 'new';

    public const TRANSITION_CANCEL = 'cancel';

    public const TRANSITION_OVERDUE = 'overdue';

    public const TRANSITION_PAY = 'pay';

    public const TRANSITION_REOPEN = 'reopen';

    public const TRANSITION_ARCHIVE = 'archive';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_NEW = 'new';

    /**
     * @return array<string, string>
     */
    public static function statusArray(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_NEW => 'New',
        ];
    }
}
