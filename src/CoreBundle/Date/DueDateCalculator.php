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

namespace SolidInvoice\CoreBundle\Date;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

/**
 * The single source for the overdue/expiry boundary. Every place that asks
 * "is this overdue" or "how many days until due" reads this class instead of
 * re-deriving the arithmetic.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Date\DueDateCalculatorTest
 */
final readonly class DueDateCalculator
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function today(): DateTimeImmutable
    {
        return $this->clock->now()->setTime(0, 0);
    }

    /**
     * An invoice due today is not overdue today; it becomes overdue at 00:00
     * on the day after the due date. This is the exclusive SQL cutoff: a
     * `due < overdueFrom()` predicate matches that rule exactly.
     */
    public function overdueFrom(): DateTimeImmutable
    {
        return $this->today();
    }

    /**
     * Signed calendar-day count. Both sides are normalised to midnight first,
     * so the result is DST-correct, unlike dividing a timestamp difference by
     * 86400.
     */
    public function daysUntilDue(?DateTimeInterface $due): ?int
    {
        if (! $due instanceof DateTimeInterface) {
            return null;
        }

        $dueDate = DateTimeImmutable::createFromInterface($due)->setTime(0, 0);

        return (int) $this->today()->diff($dueDate)->format('%r%a');
    }
}
