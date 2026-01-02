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

namespace SolidInvoice\DataGridBundle\GridBuilder\Column;

/**
 * RelativeDateColumn displays dates in relative format (e.g., "3 days ago").
 *
 * For dates older than the threshold, it falls back to absolute date format.
 * The absolute date is always shown as a tooltip on hover.
 *
 * Usage:
 *   RelativeDateColumn::new('created')
 *       ->threshold(7)  // Show relative for dates within 7 days
 *       ->absoluteFormat('d M Y')  // Format for absolute date tooltip
 */
final class RelativeDateColumn extends DateTimeColumn
{
    public static function new(string $field): static
    {
        return parent::new($field)
            ->cellClass('col-date');
    }

    /**
     * Number of days within which to show relative dates.
     * Dates older than this will show the absolute format.
     */
    private int $threshold = 7;

    /**
     * Format for the absolute date shown in the tooltip.
     */
    private string $absoluteFormat = 'd M Y';

    /**
     * Set the threshold in days for showing relative dates.
     * Dates older than this will display in absolute format.
     */
    public function threshold(int $days): self
    {
        $this->threshold = $days;
        return $this;
    }

    /**
     * Set the format for the absolute date shown in the tooltip.
     */
    public function absoluteFormat(string $format): self
    {
        $this->absoluteFormat = $format;
        return $this;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function getAbsoluteFormat(): string
    {
        return $this->absoluteFormat;
    }
}
