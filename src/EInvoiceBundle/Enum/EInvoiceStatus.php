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

namespace SolidInvoice\EInvoiceBundle\Enum;

use SolidInvoice\CoreBundle\Enum\HasStatusLabel;

/**
 * The transmission lifecycle of one {@see \SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument},
 * tracked independently of the invoice's own status — see `design` §8.1 on SOL-89 for why the two
 * cannot share a marking.
 */
enum EInvoiceStatus: string implements HasStatusLabel
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Transmitted = 'transmitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    /**
     * True for the four places with no outgoing transition. A terminal document is immutable —
     * see {@see \SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument} — and a retry creates a new
     * row rather than reusing this one.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Pending, self::Queued, self::Transmitted => false,
            self::Accepted, self::Rejected, self::Cancelled, self::Failed => true,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Queued => 'Queued',
            self::Transmitted => 'Transmitted',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Queued => 'yellow',
            self::Transmitted => 'cyan',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'gray',
            self::Failed => 'red',
        };
    }
}
