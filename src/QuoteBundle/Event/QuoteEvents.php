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

namespace SolidInvoice\QuoteBundle\Event;

final class QuoteEvents
{
    public const QUOTE_PRE_ACCEPT = 'quote.pre_accept';

    public const QUOTE_POST_ACCEPT = 'quote.post_accept';

    public const QUOTE_PRE_CANCEL = 'quote.pre_cancel';

    public const QUOTE_POST_CANCEL = 'quote.post_cancel';

    public const QUOTE_PRE_CREATE = 'quote.pre_create';

    public const QUOTE_POST_CREATE = 'quote.post_create';

    public const QUOTE_PRE_DECLINE = 'quote.pre_decline';

    public const QUOTE_POST_DECLINE = 'quote.post_decline';

    public const QUOTE_PRE_SEND = 'quote.pre_send';

    public const QUOTE_POST_SEND = 'quote.post_send';

    public const QUOTE_PRE_ARCHIVE = 'quote.pre_archive';

    public const QUOTE_POST_ARCHIVE = 'quote.post_archive';
}
