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

namespace SolidInvoice\CronBundle\Twig\Extension;

use Cron\CronExpression;
use Lorisleiva\CronTranslator\CronTranslator;
use NumberFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function array_map;
use function explode;
use function implode;

final class CronExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // new TwigFilter('cron_translate', CronTranslator::translate(...)),
            new TwigFilter('cron_translate', function ($value) {

                $cron = new CronExpression($value);

                if ($cron->getExpression(2) !== '*') {
                    $days = explode(',', $cron->getExpression(2));

                    $formatter = new NumberFormatter('en', NumberFormatter::ORDINAL);

                    $days = array_map(static fn ($day) => $formatter->format((int) $day), $days);

                    $daysFormatted = '';

                    foreach ($days as $key => $day) {
                        if ($key === 0) {
                            $daysFormatted .= $day;
                        } elseif ($key === count($days) - 1) {
                            $daysFormatted .= ' and ' . $day;
                        } else {
                            $daysFormatted .= ', ' . $day;
                        }
                    }

                    return 'On the ' . $daysFormatted . ' of every month';
                }

                $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                $parsedDays = explode(',', $cron->getExpression(4)); // Days of week

                $verboseDays = array_map(static fn ($day) => $daysOfWeek[$day], $parsedDays);

                return implode(', ', $verboseDays);
            }),
        ];
    }
}
