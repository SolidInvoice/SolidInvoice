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

namespace SolidInvoice\SaasBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidInvoice\SaasBundle\Action\ChoosePlanAction;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Integration\Options;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use function Sentry\captureException;
use function strtolower;

/**
 * @see \SolidInvoice\SaasBundle\Tests\Controller\SubscribeControllerTest
 */
class SubscribeController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelectorInterface $companySelector,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Telemetry $telemetry,
        private readonly ClockInterface $clock,
        private readonly BillingMode $billingMode,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $subscription = $this->getSubscription();
        if (! $subscription instanceof Subscription) {
            $this->addFlash('error', 'saas.flash.no_subscription_short');
            return $this->redirectToRoute('_dashboard');
        }

        $options = Options::new()
            ->withEmail($user->getEmail())
            ->withSkipTrial(! $this->grantsProviderTrial($subscription));

        // The chosen plan id (LS variant) is passed in via query parameter
        // from ChoosePlanAction / ConfirmPlanChangeAction. We swap it onto the
        // subscription IN MEMORY only — the checkout URL builder reads
        // `subscription->getPlan()->getPlanId()` for the variant — and then
        // refresh the entity afterwards to discard the in-memory mutation.
        // Nothing is flushed, so a failed checkout leaves the local plan
        // untouched. The webhook handler commits the swap on confirmation.
        $pendingPlanId = (string) $request->query->get(ChoosePlanAction::PENDING_PLAN_QUERY_PARAMETER, '');
        $swapped = false;

        if ($pendingPlanId !== '' && $pendingPlanId !== $subscription->getPlan()->getPlanId()) {
            $targetPlan = $this->planRepository->find($pendingPlanId);

            if ($targetPlan instanceof Plan && $targetPlan->isActive()) {
                $subscription->setPlan($targetPlan);
                $swapped = true;
            }
        }

        // Capture the plan the user is checking out (the in-memory swapped
        // target when a pending plan id was supplied) before the `finally`
        // block refreshes the entity and discards the swap.
        $planName = strtolower($subscription->getPlan()->getName());

        try {
            $checkoutUrl = $this->subscriptionManager
                ->getCheckoutUrl($subscription, $options);
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            captureException($e);
            $this->telemetry->event(TelemetryEvent::SaasCheckoutFailed, ['plan' => $planName]);
            $this->addFlash('error', 'saas.flash.checkout_failed');

            return $this->redirectToRoute('billing_index');
        } finally {
            // Discard the in-memory plan swap. The subscription entity is
            // reloaded from the database so the in-memory mutation is never
            // persisted. The webhook handler is the only authority for
            // committing a paid plan change locally.
            if ($swapped) {
                $this->entityManager->refresh($subscription);
            }
        }

        $this->telemetry->event(TelemetryEvent::SaasCheckoutStarted, ['plan' => $planName]);

        return $this->redirect($checkoutUrl);
    }

    /**
     * Whether this checkout should let Lemon Squeezy apply the variant's
     * built-in trial (`skip_trial: false`).
     *
     * The two billing modes disagree on what the first checkout means:
     *
     *  - Paid trial: checkout is how a trial *starts*. The subscription is
     *    still PENDING and has never been billed, so it must receive the
     *    trial. A variant configured with no trial days simply charges
     *    immediately, which is the intended "no trial" behaviour.
     *  - Free trial: the trial already ran locally, and checkout is how the
     *    user extends it by adding a card. Only an actively-trialling,
     *    never-billed subscription qualifies.
     *
     * Either way, a subscription that is already externally billed is
     * changing plans and must never receive a second trial.
     */
    private function grantsProviderTrial(Subscription $subscription): bool
    {
        if ($subscription->isExternallyBilled()) {
            return false;
        }

        if ($this->billingMode->requiresCardForTrial()) {
            return true;
        }

        return $subscription->getStatus() === SubscriptionStatus::TRIAL
            && $subscription->getEndDate() > $this->clock->now();
    }

    private function getSubscription(): ?Subscription
    {
        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return null;
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company instanceof Company) {
            return null;
        }

        return $this->subscriptionManager->getSubscriptionFor($company);
    }
}
