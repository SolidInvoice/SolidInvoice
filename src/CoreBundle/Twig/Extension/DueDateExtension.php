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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Invoices and quotes are overdue/expired from 00:00 on the day after their due
 * date, not before. Comparing raw timestamps (`(due - now) / 86400`) gets this
 * wrong for every hour of the due date itself, because `due` hydrates at
 * midnight while `now` carries a time of day. This normalises both sides to
 * midnight first and takes a calendar-day diff, which is also DST-correct.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\DueDateExtensionTest
 */
final readonly class DueDateExtension
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    #[AsTwigFunction(name: 'days_until_due')]
    public function daysUntilDue(?DateTimeInterface $due): ?int
    {
        if (! $due instanceof DateTimeInterface) {
            return null;
        }

        $today = $this->clock->now()->setTime(0, 0);
        $dueDate = DateTimeImmutable::createFromInterface($due)->setTime(0, 0);

        return (int) $today->diff($dueDate)->format('%r%a');
    }
}
