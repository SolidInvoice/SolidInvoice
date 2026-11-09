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

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;
use SolidInvoice\SaasBundle\Onboarding\OnboardingDispatcher;
use SolidInvoice\SaasBundle\Plan\DefaultPlanProvider;
use SolidInvoice\SaasBundle\Plan\TrialPeriod;
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
use Throwable;
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
        private readonly OnboardingDispatcher $onboardingDispatcher,
        private readonly LoggerInterface $logger,
        private readonly TrialPeriod $trialPeriod,
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

            $plan = $this->subscription->getPlan();

            if ($plan->isFree() && ! $this->trialPeriod->hasTrial($plan)) {
                // Free default: there is nothing to pay or trial, so the new
                // company goes straight to ACTIVE in every billing mode, whether
                // or not the user had a trial before. No Trial row: the opt-in
                // trial is still theirs.
                $this->subscriptionManager->activate($this->subscription);
                $this->sendWelcomeEmail($user, $this->subscription);
            } elseif ($this->billingMode->requiresCardForTrial()) {
                $this->recordTrialEntry($user);
                $this->redirect($event, $this->createWelcomeRedirect());
            } elseif (! $this->trialManager->userHasTrial($user) && $this->trialPeriod->hasTrial($plan)) {
                try {
                    // User is new and plan has a trial configured, start the trial atomically
                    $this->entityManager->wrapInTransaction(function () use ($user): void {
                        $this->subscriptionManager->startTrial($this->subscription);
                        $this->trialManager->createTrial($user, $this->subscription);
                    });

                    $this->sendWelcomeEmail($user, $this->subscription);
                } catch (TrialAlreadyExistsException) {
                    // Race condition: another request already created a trial for this user
                    $this->redirect($event, $this->createPlanSelectionRedirect());
                }
            } else {
                // User already had a trial — let them pick a plan for this new
                // company instead of silently checking them out on the default.
                $this->redirect($event, $this->createPlanSelectionRedirect());
            }

            $this->subscription = null;
        }
    }

    /**
     * In paid-trial mode the trial is started by the payment provider once the
     * user completes checkout, so nothing is started locally — the subscription
     * stays PENDING and RequestListener blocks the app until the webhook lands.
     *
     * The Trial row is still recorded here: the trial on this paid plan is the
     * user's one trial, and the row is what marks it used (so no opt-in trial
     * is offered later). In this mode it records "entered the trial funnel"
     * rather than "trial started".
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

    /**
     * Ticks the onboarding sequence once so the welcome email goes out at
     * signup rather than at the next hourly run. The hourly run still picks it
     * up if this fails, so a failure must not fail the signup.
     */
    private function sendWelcomeEmail(User $user, Subscription $subscription): void
    {
        try {
            $this->onboardingDispatcher->tick($user, $subscription);
        } catch (Throwable $e) {
            $this->logger->error('Could not dispatch the welcome email at signup', ['exception' => $e]);
        }
    }

    /**
     * Sends a browser on to the next billing step. A JSON client creating a company through the API
     * keeps the response it asked for: the subscription state the step resolves reaches it through
     * the API's access checks instead.
     */
    private function redirect(ResponseEvent $event, RedirectResponse $redirect): void
    {
        if (\in_array($event->getRequest()->getPreferredFormat(), ['json', 'jsonld'], true)) {
            return;
        }

        $event->setResponse($redirect);
    }

    private function createPlanSelectionRedirect(): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('saas_subscription_plans'));
    }

    private function createWelcomeRedirect(): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('saas_subscription_welcome'));
    }
}
