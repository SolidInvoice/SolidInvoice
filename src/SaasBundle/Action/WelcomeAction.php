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

use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

/**
 * The onboarding activation page shown once a card is required up front.
 *
 * Unlike the plan picker, this page never offers a choice: the company already
 * has a PENDING subscription on the default plan (set at company creation), and
 * this page simply invites the user to start their trial on it. It is the first
 * screen after company setup and — because RequestListener keeps redirecting a
 * PENDING subscription here — the screen a user returns to on every later login
 * until they activate.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Action\WelcomeActionTest
 */
final class WelcomeAction extends AbstractController
{
    public function __construct(
        private readonly SubscriptionProviderInterface $subscriptionProvider,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelectorInterface $companySelector,
        private readonly BillingMode $billingMode,
        private readonly Telemetry $telemetry,
    ) {
    }

    public function __invoke(): Response
    {
        // Free-trial mode keeps the existing plan-picker onboarding; the welcome
        // page belongs solely to the card-required flow.
        if (! $this->billingMode->requiresCardForTrial()) {
            return $this->redirectToRoute('saas_subscription_plans');
        }

        $subscription = $this->getSubscription();

        if (! $subscription instanceof Subscription) {
            $this->addFlash('error', 'saas.flash.no_subscription_short');

            return $this->redirectToRoute('_dashboard');
        }

        // Only a PENDING subscription is still mid-activation. Anything else has
        // already moved past onboarding (the trial is running, or the plan is
        // active/paused/cancelled), so hand off to the subscription overview.
        if ($subscription->getStatus() !== SubscriptionStatus::PENDING) {
            return $this->redirectToRoute('billing_index');
        }

        $this->telemetry->event(TelemetryEvent::SaasWelcomePageViewed);

        return $this->render('@SolidInvoiceSaas/subscription/welcome.html.twig', [
            'subscription' => $subscription,
            'plan' => $subscription->getPlan(),
        ]);
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

        return $this->subscriptionProvider->getSubscriptionFor($company);
    }
}
