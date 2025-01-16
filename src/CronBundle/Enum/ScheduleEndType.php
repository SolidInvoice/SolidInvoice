<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CronBundle\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ScheduleEndType: string implements TranslatableInterface
{
    case ON = 'on';
    case AFTER = 'after';
    case NEVER = 'never';

    public function isOn(): bool
    {
        return $this === self::ON;
    }

    public function isAfter(): bool
    {
        return $this === self::AFTER;
    }

    public function isNever(): bool
    {
        return $this === self::NEVER;
    }

    public function trans(TranslatorInterface $translator, string $locale = null): string
    {
        return $translator->trans(ucfirst($this->value), [], null, $locale);
    }

    public function formLabel(): string
    {
        return match ($this) {
            self::NEVER => 'Never',
            self::AFTER => 'After x occurrences',
            self::ON => 'On the following date',
        };
    }
}
