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

namespace SolidInvoice\SaasBundle\Company;

use SolidInvoice\CoreBundle\Company\UserEligibleCompanies;
use SolidInvoice\SaasBundle\Service\SubscriptionEligibility;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * Filters the user's company list down to those with an active (or in-grace)
 * subscription. Only registered when the SaaS bundle is loaded
 * (`SOLIDINVOICE_PLATFORM=saas`); self-hosted installs continue to use the
 * default {@see \SolidInvoice\CoreBundle\Company\AllUserCompanies}.
 */
#[AsDecorator(decorates: UserEligibleCompanies::class)]
final readonly class SubscriptionAwareUserCompanies implements UserEligibleCompanies
{
    public function __construct(
        private UserEligibleCompanies $inner,
        private SubscriptionEligibility $eligibility,
    ) {
    }

    public function getFor(User $user): array
    {
        return array_values(array_filter(
            $this->inner->getFor($user),
            $this->eligibility->isActive(...),
        ));
    }
}
