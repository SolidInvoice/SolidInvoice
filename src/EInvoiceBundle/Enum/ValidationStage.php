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

enum ValidationStage: string
{
    case Schema = 'schema';
    case Syntax = 'syntax';
    case Business = 'business';
    case Archival = 'archival';

    public function order(): int
    {
        return match ($this) {
            self::Schema => 0,
            self::Syntax => 1,
            self::Business => 2,
            self::Archival => 3,
        };
    }
}
