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

namespace SolidInvoice\ClientBundle\Enum;

use SolidInvoice\CoreBundle\Enum\HasStatusLabel;
use SolidInvoice\CoreBundle\Enum\StatusVariant;

enum ClientStatus: string implements HasStatusLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Archived => 'Archived',
        };
    }

    public function getVariant(): StatusVariant
    {
        return match ($this) {
            // Active is the normal state for most clients. Success is reserved for money,
            // so a chip on nearly every row does not compete with the Paid amounts.
            self::Active => StatusVariant::Info,
            self::Inactive => StatusVariant::Neutral,
            self::Archived => StatusVariant::Neutral,
        };
    }
}
