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

use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;

/**
 * Single source of truth for "is this company allowed to use paid features right now".
 *
 * Used by both {@see \SolidInvoice\SaasBundle\Security\Voter\SubscriptionVoter} (runtime
 * access on every request) and {@see \SolidInvoice\SaasBundle\Company\SubscriptionAwareUserCompanies}
 * (filtering company pickers before the user even gets to authorise).
 * @see \SolidInvoice\SaasBundle\Tests\Service\SubscriptionEligibilityTest
 */
final readonly class SubscriptionEligibility implements PaidSubscriptionGateInterface
{
    public function __construct(
        private SubscriptionProviderInterface $subscriptionProvider,
        private ClockInterface $clock,
    ) {
    }

    public function evaluate(Company $company): EligibilityResult
    {
        $subscription = $this->subscriptionProvider->getSubscriptionFor($company);

        if (! $subscription instanceof Subscription) {
            return EligibilityResult::active();
        }

        $now = $this->clock->now();

        return match ($subscription->getStatus()) {
            SubscriptionStatus::ACTIVE => EligibilityResult::active(),
            SubscriptionStatus::TRIAL => $subscription->getEndDate() > $now
                ? EligibilityResult::active()
                : EligibilityResult::denied('Your trial has ended. Activate a subscription to continue using this resource.'),
            SubscriptionStatus::CANCELLED, SubscriptionStatus::EXPIRED => $subscription->getEndDate() > $now
                ? EligibilityResult::active()
                : EligibilityResult::denied('Your subscription has ended. Renew it to continue using this resource.'),
            SubscriptionStatus::PAUSED => EligibilityResult::denied('Your subscription is currently paused. Reactivate it to continue using this resource.'),
            SubscriptionStatus::PENDING => EligibilityResult::denied('Your subscription payment is still being processed. Access will resume once it completes.'),
            // Any other known-or-future SubscriptionStatus (INACTIVE, PAST_DUE, UNPAID, …)
            // fails closed. Future enum cases must be wired up explicitly above.
            default => EligibilityResult::denied('Your subscription is not currently active.'),
        };
    }

    public function isActive(Company $company): bool
    {
        return $this->evaluate($company)->active;
    }

    /**
     * Stricter than {@see self::isActive()}: only paying tenants qualify.
     *
     * Used to gate the REST API and MCP, which are paid-plan features. A trial
     * (even on a higher plan) and a free/no-subscription tenant are denied,
     * while a cancelled or expired subscription still counts while the already
     * paid-for term has not yet elapsed.
     */
    public function isPaid(Company $company): bool
    {
        $subscription = $this->subscriptionProvider->getSubscriptionFor($company);

        if (! $subscription instanceof Subscription) {
            return false;
        }

        return match ($subscription->getStatus()) {
            SubscriptionStatus::ACTIVE => true,
            SubscriptionStatus::CANCELLED, SubscriptionStatus::EXPIRED => $subscription->getEndDate() > $this->clock->now(),
            // TRIAL, PAUSED, PENDING and any future status are not paid access.
            default => false,
        };
    }
}
