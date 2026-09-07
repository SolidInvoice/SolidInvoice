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
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use function Sentry\captureException;

/**
 * @see \SolidInvoice\SaasBundle\Tests\Controller\SubscribeControllerTest
 */
class CustomerPortalAction extends AbstractController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelectorInterface $companySelector,
        private readonly Telemetry $telemetry,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $subscription = $this->getSubscription();
        if (! $subscription instanceof Subscription) {
            $this->addFlash('error', 'saas.flash.no_subscription_short');
            return $this->redirectToRoute('_dashboard');
        }

        try {
            $customerPortalUrl = $this->subscriptionManager
                ->getCustomerPortalUrl($subscription);

            return $this->redirect($customerPortalUrl);
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            captureException($e);
            $this->addFlash('error', 'saas.flash.checkout_failed');

            return $this->redirectToRoute('billing_index');
        } finally {
            $this->telemetry->event(TelemetryEvent::SaasCustomerPortalOpened);
        }
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
