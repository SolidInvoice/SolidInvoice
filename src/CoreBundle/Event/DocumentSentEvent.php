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

namespace SolidInvoice\CoreBundle\Event;

use SolidInvoice\CoreBundle\Enum\SendChannel;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * An invoice or quote was sent to the client, by email or (for a mark-sent) by some channel
 * outside the app. Dispatched once per send.
 */
final class DocumentSentEvent extends Event
{
    /**
     * @param list<string> $recipients email addresses; empty when the channel is not email
     */
    public function __construct(
        public readonly Invoice | Quote $document,
        public readonly SendChannel $channel,
        public readonly array $recipients = [],
    ) {
    }
}
