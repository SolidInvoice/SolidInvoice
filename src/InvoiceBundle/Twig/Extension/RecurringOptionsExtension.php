<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Twig\Extension;

use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RecurringOptionsExtension extends AbstractExtension
{
    public function __construct(
        private readonly RecurringSchedule $schedule
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('recurring_frequency', $this->getRecurringFrequency(...)),
            new TwigFunction('recurring_occurrences', $this->schedule->getNextOccurrences(...)),
        ];
    }

    public function getRecurringFrequency(RecurringOptions $recurringOptions): string
    {
        $frequency = $this->schedule->getFrequency($recurringOptions);

        if (! $frequency) {
            return '';
        }

        $format = match ($recurringOptions->getType()) {
            ScheduleRecurringType::YEARLY => 'F Y',
            default => 'd F Y',
        };

        /*if (! isset($this->endType) || ! $this->recurringInvoice->getDateStart() instanceof DateTimeInterface) {
            return $frequency;
        }*/

        if (! $recurringOptions->hasEndType()) {
            return $frequency;
        }

        return $frequency . match ($recurringOptions->getEndType()) {
            ScheduleEndType::ON => sprintf(' from %s to %s', $recurringOptions->getRecurringInvoice()->getDateStart()?->format($format), $this->schedule->getEndDate($recurringOptions)?->format($format)),
            ScheduleEndType::AFTER => sprintf(' from %s to %s (%d occurrences)', $recurringOptions->getRecurringInvoice()->getDateStart()?->format($format), $this->schedule->getEndDate($recurringOptions)?->format($format), $recurringOptions->getEndOccurrence()),
            ScheduleEndType::NEVER => sprintf(' from %s', $recurringOptions->getRecurringInvoice()->getDateStart()?->format($format)),
        };
    }
}
