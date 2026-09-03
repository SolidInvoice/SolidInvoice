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

namespace SolidInvoice\SaasBundle\Plan;

use Carbon\CarbonInterval;
use DateInterval;
use SolidWorx\Platform\SaasBundle\Entity\Plan;

/**
 * Reads a plan's trial length in whole days.
 *
 * Plan::trialDuration is a DateInterval, which cannot be reduced to a day
 * count reliably on its own (`->days` is only populated on diff-derived
 * intervals), so the conversion lives here rather than being repeated at each
 * call site — including Twig, which cannot do it at all.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Plan\TrialPeriodTest
 */
final readonly class TrialPeriod
{
    /**
     * Whole days of trial the plan grants, or null when it has no trial
     * configured (or one that rounds down to nothing).
     */
    public function days(Plan $plan): ?int
    {
        $trialDuration = $plan->getTrialDuration();

        if (! $trialDuration instanceof DateInterval) {
            return null;
        }

        $days = (int) CarbonInterval::instance($trialDuration)->total('days');

        return $days > 0 ? $days : null;
    }
}
