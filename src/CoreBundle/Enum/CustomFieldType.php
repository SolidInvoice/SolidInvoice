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

enum CustomFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case DATE = 'date';
    case EMAIL = 'email';
    case URL = 'url';
    case CHECKBOX = 'checkbox';
    case SELECT = 'select';
    case MULTI_SELECT = 'multi_select';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::TEXTAREA => 'Long text',
            self::NUMBER => 'Number',
            self::DATE => 'Date',
            self::EMAIL => 'Email',
            self::URL => 'URL',
            self::CHECKBOX => 'Checkbox',
            self::SELECT => 'Single-select',
            self::MULTI_SELECT => 'Multi-select',
        };
    }

    public function requiresOptions(): bool
    {
        return $this === self::SELECT || $this === self::MULTI_SELECT;
    }
}
