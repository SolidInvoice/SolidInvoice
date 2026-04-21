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

namespace SolidInvoice\SaasBundle\Checklist;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ActivateSubscriptionItem implements ChecklistItemInterface
{
    public function __construct(
        private SubscriptionProviderInterface $subscriptionProvider,
        private CompanySelector $companySelector,
        private CompanyRepository $companyRepository,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.activate_subscription.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.activate_subscription.description';
    }

    public function getIcon(): string
    {
        return 'tabler:credit-card';
    }

    public function getRoute(): string
    {
        return 'saas_subscription_checkout';
    }

    public function getPriority(): int
    {
        return -1000; // Lowest priority - should appear last
    }

    public function active(): bool
    {
        return true;
    }

    public function isComplete(): bool
    {
        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return false;
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company) {
            return false;
        }

        $subscription = $this->subscriptionProvider->getSubscriptionFor($company);

        if (! $subscription instanceof Subscription) {
            return false;
        }

        // Consider complete if subscription is active
        return $subscription->getStatus() === SubscriptionStatus::ACTIVE;
    }
}
