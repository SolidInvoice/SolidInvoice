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

namespace SolidInvoice\InvoiceBundle\Twig\Extension;

use Override;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function array_filter;

final class RecurringOptionsExtension extends AbstractExtension
{
    public function __construct(
        private readonly RecurringSchedule $schedule,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('recurring_frequency', $this->getRecurringFrequency(...)),
            new TwigFunction('recurring_occurrences', $this->schedule->getNextOccurrences(...)),
            new TwigFunction('recurring_end_date', $this->schedule->getEndDate(...)),
        ];
    }

    public function getRecurringFrequency(RecurringOptions $recurringOptions, bool $includeStartEndDates = true): string
    {
        $frequency = $this->schedule->getFrequency($recurringOptions);

        if ($frequency === '' || $frequency === '0') {
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

        if (! $includeStartEndDates) {
            return $frequency;
        }

        $parameters = [
            'frequency' => $frequency,
            'start' => $recurringOptions->getRecurringInvoice()->getDateStart()?->format($format),
            'end' => $this->schedule->getEndDate($recurringOptions)?->format($format),
            'count' => $recurringOptions->getEndOccurrence(),
        ];

        return $this->translator->trans(
            'invoice.recurring.schedule_summary.' . $recurringOptions->getEndType()->value,
            array_filter($parameters, static fn (mixed $value): bool => $value !== null),
        );
    }
}
