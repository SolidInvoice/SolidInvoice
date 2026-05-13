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

namespace SolidInvoice\TaxBundle\Enum;

enum TaxType: string
{
    case Inclusive = 'Inclusive';
    case Exclusive = 'Exclusive';
    case FlatRate = 'Flat Rate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inclusive => 'Inclusive',
            self::Exclusive => 'Exclusive',
            self::FlatRate => 'Flat Rate',
        };
    }
}
