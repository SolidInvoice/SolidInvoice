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

namespace SolidInvoice\InvoiceBundle\Enum;

use SolidInvoice\CoreBundle\Enum\HasStatusLabel;

enum CreditNoteStatus: string implements HasStatusLabel
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Issued => 'green',
            self::Cancelled => 'gray',
        };
    }
}
