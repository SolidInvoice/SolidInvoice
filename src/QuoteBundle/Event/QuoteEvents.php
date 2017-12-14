<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Event;

final class QuoteEvents
{
    const QUOTE_PRE_ACCEPT = 'quote.pre_accept';

    const QUOTE_POST_ACCEPT = 'quote.post_accept';

    const QUOTE_PRE_CANCEL = 'quote.pre_cancel';

    const QUOTE_POST_CANCEL = 'quote.post_cancel';

    const QUOTE_PRE_CREATE = 'quote.pre_create';

    const QUOTE_POST_CREATE = 'quote.post_create';

    const QUOTE_PRE_DECLINE = 'quote.pre_decline';

    const QUOTE_POST_DECLINE = 'quote.post_decline';

    const QUOTE_PRE_SEND = 'quote.pre_send';

    const QUOTE_POST_SEND = 'quote.post_send';

    const QUOTE_PRE_ARCHIVE = 'quote.pre_archive';

    const QUOTE_POST_ARCHIVE = 'quote.post_archive';
}
