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
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;

final readonly class OnboardingContext
{
    public function __construct(
        public User $user,
        public Company $company,
        public Subscription $subscription,
        public Plan $plan,
        public DateTimeImmutable $trialStart,
        public DateTimeImmutable $trialEnd,
    ) {
    }
}
