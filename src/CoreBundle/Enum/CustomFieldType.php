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
            self::TEXT => 'custom_field.type.text',
            self::TEXTAREA => 'custom_field.type.textarea',
            self::NUMBER => 'custom_field.type.number',
            self::DATE => 'custom_field.type.date',
            self::EMAIL => 'custom_field.type.email',
            self::URL => 'custom_field.type.url',
            self::CHECKBOX => 'custom_field.type.checkbox',
            self::SELECT => 'custom_field.type.select',
            self::MULTI_SELECT => 'custom_field.type.multi_select',
        };
    }

    public function requiresOptions(): bool
    {
        return $this === self::SELECT || $this === self::MULTI_SELECT;
    }
}
