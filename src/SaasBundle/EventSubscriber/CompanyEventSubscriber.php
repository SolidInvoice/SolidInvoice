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
use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Exception\TrialAlreadyExistsException;
use SolidWorx\Platform\SaasBundle\Integration\Options;
use SolidWorx\Platform\SaasBundle\Integration\PaymentIntegrationInterface;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepository;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Trial\TrialManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function assert;

#[AsEventListener(CompanyCreatedEvent::class, 'onCompanyCreated')]
#[AsEventListener(KernelEvents::RESPONSE, 'onResponse')]
final class CompanyEventSubscriber
{
    private ?Subscription $subscription = null;

    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly PaymentIntegrationInterface $paymentIntegration,
        private readonly Security $security,
        private readonly TrialManagerInterface $trialManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function onCompanyCreated(CompanyCreatedEvent $event): void
    {
        // We only have a single plan for now, so we can just get the first one
        $plan = $this->planRepository->findOneBy([]);

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

            if (! $this->trialManager->userHasTrial($user) && $plan->getTrialDuration() !== null) {
                try {
                    // User is new and plan has a trial configured, start the trial atomically
                    $this->entityManager->wrapInTransaction(function () use ($user): void {
                        $this->subscriptionManager->startTrial($this->subscription);
                        $this->trialManager->createTrial($user, $this->subscription);
                    });
                } catch (TrialAlreadyExistsException) {
                    // Race condition: another request already created a trial for this user
                    $event->setResponse($this->createCheckoutRedirect($this->subscription, $user));
                }
            } else {
                // User already had a trial or plan has no trial, redirect to checkout
                $event->setResponse($this->createCheckoutRedirect($this->subscription, $user));
            }

            $this->subscription = null;
        }
    }

    private function createCheckoutRedirect(Subscription $subscription, User $user): RedirectResponse
    {
        $checkoutUrl = $this->paymentIntegration->checkout(
            $subscription,
            Options::new()->withEmail($user->getEmail())->withSkipTrial(true),
        );

        return new RedirectResponse($checkoutUrl);
    }
}
