<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\EventSubscriber;

use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;
use SolidInvoice\SaasBundle\Repository\TrialRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Integration\Options;
use SolidWorx\Platform\SaasBundle\Integration\PaymentIntegrationInterface;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepository;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
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
        private readonly TrialRepository $trialRepository,
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

            if ($this->trialRepository->userHasTrial($user)) {
                // User already had a free trial, so we just activate the subscription
                $checkoutUrl = $this->paymentIntegration->checkout($this->subscription, Options::new()->withEmail($user->getEmail())->withSkipTrial(true));
                $event->setResponse(new RedirectResponse($checkoutUrl));
            } else {
                // User is new, so we create a new free trial
                $this->subscription->setStatus(SubscriptionStatus::TRIAL);
                $this->subscription->setEndDate($this->subscription->getStartDate()->add(new \DateInterval('P7D'))); // @TODO: Trial should be configurable
                $this->trialRepository->createTrial($user, $this->subscription);
            }

            $this->subscription = null;
        }
    }
}
