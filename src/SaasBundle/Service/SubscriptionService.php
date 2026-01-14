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

namespace SolidInvoice\SaasBundle\Service;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Uid\Ulid;

final readonly class SubscriptionService
{
    public function __construct(
        private ToggleInterface $toggler,
        private SubscriptionProviderInterface $subscriptionProvider,
        private CompanySelector $companySelector,
        private CompanyRepository $companyRepository,
    ) {
    }

    public function isTrialSubscription(): bool
    {
        // Return false if SaaS is not enabled
        if (! $this->toggler->isActive('saas_enabled')) {
            return false;
        }

        try {
            $companyId = $this->companySelector->getCompany();
            if (! $companyId instanceof Ulid) {
                return false;
            }

            $company = $this->companyRepository->find($companyId);
            if (! $company) {
                return false;
            }

            $subscription = $this->subscriptionProvider->getSubscriptionFor($company);

            return $subscription?->getStatus() === SubscriptionStatus::TRIAL;
        } catch (\Exception) {
            // Handle cases where subscription tables don't exist (self-hosted, dev environments)
            return false;
        }
    }
}
