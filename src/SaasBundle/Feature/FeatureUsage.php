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

namespace SolidInvoice\SaasBundle\Feature;

/**
 * Pure helper for "approaching limit" decisions on quota-style features.
 *
 * The threshold rule is `max(1, ceil(total * 0.1))` — i.e. trigger when 10%
 * of the quota remains, but never less than 1 remaining unit. Kept as a
 * stateless VO so banner partials, listeners, and onboarding emails can all
 * agree on when the warning should fire without each reinventing the maths.
 * @see \SolidInvoice\SaasBundle\Tests\Feature\FeatureUsageTest
 */
final readonly class FeatureUsage
{
    public function isApproachingLimit(int $remaining, int $total): bool
    {
        if ($total <= 0 || $remaining < 0) {
            return false;
        }

        $threshold = max(1, (int) ceil($total * 0.1));

        return $remaining <= $threshold;
    }
}
