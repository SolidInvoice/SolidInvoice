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

use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @see \SolidInvoice\SaasBundle\Tests\Checklist\ActivateSubscriptionItemTest
 */
final readonly class ActivateSubscriptionItem implements ChecklistItemInterface
{
    public function __construct(
        private SubscriptionProviderInterface $subscriptionProvider,
        private CompanySelectorInterface $companySelector,
        private CompanyRepository $companyRepository,
        private BillingMode $billingMode,
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
        // In paid-trial mode a company cannot reach the dashboard without an
        // active subscription in the first place, so prompting them to activate
        // one is redundant.
        if ($this->billingMode->requiresCardForTrial()) {
            return false;
        }

        // An ACTIVE Free subscription is Free forever, not one awaiting activation.
        $subscription = $this->subscription();

        return ! ($subscription instanceof Subscription
            && $subscription->getStatus() === SubscriptionStatus::ACTIVE
            && $subscription->getPlan()->isFree());
    }

    public function isComplete(): bool
    {
        return $this->subscription()?->getStatus() === SubscriptionStatus::ACTIVE;
    }

    private function subscription(): ?Subscription
    {
        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return null;
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company instanceof Company) {
            return null;
        }

        return $this->subscriptionProvider->getSubscriptionFor($company);
    }
}
