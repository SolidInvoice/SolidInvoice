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

namespace SolidInvoice\SaasBundle\Onboarding;

use DateTimeImmutable;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use function intdiv;
use function max;

/**
 * Spreads onboarding emails proportionally across the trial window so the
 * cadence adapts to each Plan's trialDuration without hard-coded day offsets.
 */
final class OnboardingScheduleCalculator
{
    /**
     * Target send time for the step at the given zero-based position, relative
     * to the subscription's trial start.
     *
     * With N steps over duration D: spacing = D / N, step i fires at
     * trialStart + i * spacing. Step 0 fires at trial start; the last step
     * fires just before trial end, leaving the upgrade offer anchored near
     * expiry regardless of trial length.
     */
    public function targetTimeFor(Subscription $subscription, int $stepIndex, int $stepCount): DateTimeImmutable
    {
        $trialStart = DateTimeImmutable::createFromInterface($subscription->getStartDate());

        if ($stepIndex <= 0 || $stepCount <= 1) {
            return $trialStart;
        }

        $trialEnd = DateTimeImmutable::createFromInterface($subscription->getEndDate());
        $totalSeconds = max(0, $trialEnd->getTimestamp() - $trialStart->getTimestamp());
        $spacingSeconds = intdiv($totalSeconds, $stepCount);
        $offsetSeconds = $spacingSeconds * $stepIndex;

        return $trialStart->modify('+' . $offsetSeconds . ' seconds');
    }
}
