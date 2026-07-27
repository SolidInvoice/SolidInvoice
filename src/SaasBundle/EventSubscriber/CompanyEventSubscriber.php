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

namespace SolidInvoice\SaasBundle\EventSubscriber;

use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;
use SolidInvoice\SaasBundle\Plan\DefaultPlanProvider;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Exception\TrialAlreadyExistsException;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Trial\TrialManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function assert;

#[AsEventListener(CompanyCreatedEvent::class, 'onCompanyCreated')]
#[AsEventListener(KernelEvents::RESPONSE, 'onResponse')]
final class CompanyEventSubscriber
{
    private ?Subscription $subscription = null;

    public function __construct(
        private readonly DefaultPlanProvider $defaultPlanProvider,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly Security $security,
        private readonly TrialManagerInterface $trialManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly BillingMode $billingMode,
    ) {
    }

    public function onCompanyCreated(CompanyCreatedEvent $event): void
    {
        $plan = $this->defaultPlanProvider->get();

        if ($plan instanceof Plan) {
            $this->subscription = $this->subscriptionManager->createSubscription(
                $event->company,
                $plan,
            );
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($this->subscription instanceof Subscription) {
            $user = $this->security->getUser();
            assert($user instanceof User);

            if ($this->billingMode->requiresCardForTrial()) {
                $this->recordTrialEntry($user);
                $event->setResponse($this->createPlanSelectionRedirect());
                $this->subscription = null;

                return;
            }

            $plan = $this->subscription->getPlan();

            if (! $this->trialManager->userHasTrial($user) && $plan->getTrialDuration() instanceof DateInterval) {
                try {
                    // User is new and plan has a trial configured, start the trial atomically
                    $this->entityManager->wrapInTransaction(function () use ($user): void {
                        $this->subscriptionManager->startTrial($this->subscription);
                        $this->trialManager->createTrial($user, $this->subscription);
                    });
                } catch (TrialAlreadyExistsException) {
                    // Race condition: another request already created a trial for this user
                    $event->setResponse($this->createPlanSelectionRedirect());
                }
            } else {
                // User already had a trial — let them pick a plan for this new
                // company instead of silently checking them out on the default.
                $event->setResponse($this->createPlanSelectionRedirect());
            }

            $this->subscription = null;
        }
    }

    /**
     * In paid-trial mode the trial is started by the payment provider once the
     * user completes checkout, so nothing is started locally — the subscription
     * stays PENDING and RequestListener blocks the app until the webhook lands.
     *
     * The Trial row is still recorded here. It is the ledger the onboarding
     * email scheduler joins against, so skipping it would silently stop the
     * whole onboarding sequence, not just the trial-specific emails. In this
     * mode it records "entered the trial funnel" rather than "trial started".
     */
    private function recordTrialEntry(User $user): void
    {
        if ($this->trialManager->userHasTrial($user)) {
            return;
        }

        try {
            $this->trialManager->createTrial($user, $this->subscription);
        } catch (TrialAlreadyExistsException) {
            // Race condition: another request already recorded a trial for this
            // user. Harmless — the user is headed to plan selection regardless.
        }
    }

    private function createPlanSelectionRedirect(): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('saas_subscription_plans'));
    }
}
