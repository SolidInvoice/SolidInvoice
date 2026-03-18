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

namespace SolidInvoice\TimeTrackingBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class DurationExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_duration', $this->formatDuration(...)),
        ];
    }

    public function formatDuration(int|float|null $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '0:00';
        }

        $total = (int) round($seconds);
        $hours = (int) floor($total / 3600);
        $minutes = (int) floor(($total % 3600) / 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
