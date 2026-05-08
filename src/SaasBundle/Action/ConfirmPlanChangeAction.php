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
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Exception\PaymentIntegrationException;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

final class ConfirmPlanChangeAction extends AbstractController
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly SubscriptionProviderInterface $subscriptionProvider,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('change_plan', (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Invalid security token, please try again.');

            return $this->redirectToRoute('saas_subscription_change');
        }

        $subscription = $this->getSubscription();

        if (! $subscription instanceof Subscription) {
            $this->addFlash('error', 'No subscription found.');

            return $this->redirectToRoute('saas_subscription_plans');
        }

        $planId = (string) $request->request->get('plan', '');
        $plan = $planId === '' ? null : $this->planRepository->find($planId);

        if (! $plan instanceof Plan || ! $plan->isActive()) {
            $this->addFlash('error', 'The selected plan is invalid.');

            return $this->redirectToRoute('saas_subscription_change');
        }

        if ($plan->getPlanId() === $subscription->getPlan()->getPlanId()) {
            return $this->redirectToRoute('billing_index');
        }

        $isDowngrade = $plan->getPrice() < $subscription->getPlan()->getPrice();
        $confirmed = $request->request->getBoolean('confirmed');

        if ($isDowngrade && ! $confirmed) {
            return $this->render('@SolidInvoiceSaas/subscription/_change_confirm.html.twig', [
                'subscription' => $subscription,
                'currentPlan' => $subscription->getPlan(),
                'newPlan' => $plan,
            ]);
        }

        if ($subscription->getStatus() === SubscriptionStatus::ACTIVE && $subscription->isExternallyBilled()) {
            return $this->handleActivePlanChange($subscription, $plan, $isDowngrade);
        }

        // From here the subscription is either pending, on a trial, or
        // already active on the free plan — none of which involve the
        // payment provider on the existing record. Swap the plan and route
        // to the appropriate finishing step.
        $this->subscriptionManager->changePlan($subscription, $plan);

        if ($plan->isFree()) {
            $this->subscriptionManager->activate($subscription);
            $this->addFlash('success', 'Your plan has been changed.');

            return $this->redirectToRoute('billing_index');
        }

        return $this->redirectToRoute('saas_subscription_checkout');
    }

    private function handleActivePlanChange(Subscription $subscription, Plan $plan, bool $isDowngrade): Response
    {
        try {
            if ($isDowngrade && $plan->isFree()) {
                $this->subscriptionManager->scheduleDowngrade($subscription, $plan);
                $this->addFlash(
                    'success',
                    'Your plan will be downgraded at the end of the current billing period.',
                );

                return $this->redirectToRoute('billing_index');
            }

            $this->subscriptionManager->changeActivePlan($subscription, $plan);
            $this->addFlash('success', 'Your plan has been updated.');
        } catch (PaymentIntegrationException $e) {
            $this->addFlash('error', sprintf('Could not update your plan: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('billing_index');
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
