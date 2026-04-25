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

namespace SolidInvoice\SaasBundle\Security\Voter;

use Psr\Clock\ClockInterface;
use SolidInvoice\ApiBundle\Security\Attribute as ApiAttribute;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\McpBundle\Security\Attribute as McpAttribute;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Uid\Ulid;
use Throwable;

final class SubscriptionVoter extends Voter
{
    public function __construct(
        private readonly ToggleInterface $toggler,
        private readonly SubscriptionProviderInterface $subscriptionProvider,
        private readonly CompanySelector $companySelector,
        private readonly CompanyRepository $companyRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === McpAttribute::ACCESS || $attribute === ApiAttribute::ACCESS;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        try {
            if (! $this->toggler->isActive('saas_enabled')) {
                return true;
            }

            // Without a resolved company we can't check the subscription state. Fail closed
            // so a misconfigured CompanySelector can't accidentally expose cross-tenant data.
            $companyId = $this->companySelector->getCompany();
            if (! $companyId instanceof Ulid) {
                return $this->deny($vote, 'No company is associated with this request.');
            }

            $company = $this->companyRepository->find($companyId);
            if ($company === null) {
                return $this->deny($vote, 'No company is associated with this request.');
            }

            $subscription = $this->subscriptionProvider->getSubscriptionFor($company);
            if (! $subscription instanceof Subscription) {
                return true;
            }

            $now = $this->clock->now();

            return match ($subscription->getStatus()) {
                SubscriptionStatus::ACTIVE => true,
                SubscriptionStatus::TRIAL => $subscription->getEndDate() > $now
                    ? true
                    : $this->deny($vote, 'Your trial has ended. Activate a subscription to continue using this resource.'),
                SubscriptionStatus::CANCELLED, SubscriptionStatus::EXPIRED => $subscription->getEndDate() > $now
                    ? true
                    : $this->deny($vote, 'Your subscription has ended. Renew it to continue using this resource.'),
                SubscriptionStatus::PAUSED => $this->deny($vote, 'Your subscription is currently paused. Reactivate it to continue using this resource.'),
                SubscriptionStatus::PENDING => $this->deny($vote, 'Your subscription payment is still being processed. Access will resume once it completes.'),
                default => true,
            };
        } catch (Throwable) {
            return true;
        }
    }

    private function deny(?Vote $vote, string $reason): bool
    {
        $vote?->addReason($reason);

        return false;
    }
}
