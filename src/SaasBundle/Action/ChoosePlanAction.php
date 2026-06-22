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

namespace SolidInvoice\SaasBundle\Action;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use function strtolower;

/**
 * @see \SolidInvoice\SaasBundle\Tests\Action\ChoosePlanActionTest
 */
final class ChoosePlanAction extends AbstractController
{
    /**
     * Query parameter used to hand off the desired plan id to the SaaS
     * checkout route. The subscription's `plan` field is intentionally NOT
     * mutated here for paid plans — that switch only commits once Lemon
     * Squeezy confirms the upgrade via webhook (see SubscriptionPlanSyncListener).
     */
    public const string PENDING_PLAN_QUERY_PARAMETER = 'plan';

    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly SubscriptionProviderInterface $subscriptionProvider,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
        private readonly Telemetry $telemetry,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('choose_plan', (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Invalid security token, please try again.');

            return $this->redirectToRoute('saas_subscription_plans');
        }

        $subscription = $this->getSubscription();

        if (! $subscription instanceof Subscription) {
            $this->addFlash('error', 'No subscription found');

            return $this->redirectToRoute('_dashboard');
        }

        if ($subscription->getStatus() === SubscriptionStatus::ACTIVE) {
            return $this->redirectToRoute('billing_index');
        }

        $planId = (string) $request->request->get('plan', '');
        $plan = $planId === '' ? null : $this->planRepository->find($planId);

        if (! $plan instanceof Plan || ! $plan->isActive()) {
            $this->addFlash('error', 'The selected plan is invalid.');

            return $this->redirectToRoute('saas_subscription_plans');
        }

        $this->telemetry->event(TelemetryEvent::SaasPlanSelected, [
            'plan' => strtolower($plan->getName()),
            'is_paid' => ! $plan->isFree(),
        ]);

        // Free plan: no Lemon Squeezy round-trip; safe to commit locally now.
        if ($plan->isFree()) {
            if ($subscription->getPlan()->getPlanId() !== $plan->getPlanId()) {
                $this->subscriptionManager->changePlan($subscription, $plan);
            }

            $this->subscriptionManager->activate($subscription);
            $this->addFlash('success', 'Your free plan is now active.');

            return $this->redirectToRoute('_dashboard');
        }

        // Paid plan: defer the local plan switch. Pass the desired plan id
        // to the checkout route via query parameter — the webhook handler
        // commits the switch only if Lemon Squeezy confirms the new
        // subscription / variant.
        return $this->redirectToRoute('saas_subscription_checkout', [
            self::PENDING_PLAN_QUERY_PARAMETER => $plan->getPlanId(),
        ]);
    }

    private function getSubscription(): ?Subscription
    {
        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return null;
        }

        $company = $this->companyRepository->find($companyId);

        if ($company === null) {
            return null;
        }

        return $this->subscriptionProvider->getSubscriptionFor($company);
    }
}
