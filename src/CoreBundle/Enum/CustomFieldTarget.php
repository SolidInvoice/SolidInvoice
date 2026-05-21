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

namespace SolidInvoice\CoreBundle\Enum;

enum CustomFieldTarget: string
{
    case CLIENT = 'CLIENT';
    case CONTACT = 'CONTACT';
    case INVOICE = 'INVOICE';
    case QUOTE = 'QUOTE';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::CONTACT => 'Contact',
            self::INVOICE => 'Invoice',
            self::QUOTE => 'Quote',
        };
    }

    public function supportsVisibility(): bool
    {
        return $this === self::INVOICE || $this === self::QUOTE;
    }
}
