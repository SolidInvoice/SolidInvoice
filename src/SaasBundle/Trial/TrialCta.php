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

namespace SolidInvoice\SaasBundle\Trial;

use SolidWorx\Platform\SaasBundle\Entity\Plan;

/**
 * A trial a user can take right now: start one on `plan`, or move a running
 * trial up to it.
 */
final readonly class TrialCta
{
    public function __construct(
        public Plan $plan,
        public ?int $days,
        public bool $movingUp,
    ) {
    }
}
