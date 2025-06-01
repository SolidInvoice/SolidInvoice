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
use function ucfirst;

enum ScheduleRecurringType: string implements TranslatableInterface
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(ucfirst($this->value), [], null, $locale);
    }

    public function isDaily(): bool
    {
        return $this === self::DAILY;
    }

    public function isWeekly(): bool
    {
        return $this === self::WEEKLY;
    }

    public function isMonthly(): bool
    {
        return $this === self::MONTHLY;
    }

    public function isYearly(): bool
    {
        return $this === self::YEARLY;
    }
}
