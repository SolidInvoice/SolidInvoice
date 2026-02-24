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

enum QuoteStatus: string
{
    case New = 'new';
    case Draft = 'draft';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Cancelled = 'cancelled';
    case Declined = 'declined';
    case Archived = 'archived';
}
