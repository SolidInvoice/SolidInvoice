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

namespace SolidInvoice\QuoteBundle\Model;

final class Graph
{
    public const TRANSITION_NEW = 'new';

    public const TRANSITION_SEND = 'send';

    public const TRANSITION_PUBLISH = 'publish';

    public const TRANSITION_CANCEL = 'cancel';

    public const TRANSITION_DECLINE = 'decline';

    public const TRANSITION_ACCEPT = 'accept';

    public const TRANSITION_REOPEN = 'reopen';

    public const TRANSITION_ARCHIVE = 'archive';

    public const STATUS_NEW = 'new';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @return array<string, string>
     */
    public static function statusArray(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
