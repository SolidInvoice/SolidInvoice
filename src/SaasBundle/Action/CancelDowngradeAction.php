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
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Exception\PaymentIntegrationException;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

final class CancelDowngradeAction extends AbstractController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly SubscriptionProviderInterface $subscriptionProvider,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('cancel_downgrade', (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Invalid security token, please try again.');

            return $this->redirectToRoute('billing_index');
        }

        $subscription = $this->getSubscription();

        if (! $subscription instanceof Subscription || ! $subscription->hasPendingPlanChange()) {
            return $this->redirectToRoute('billing_index');
        }

        try {
            $this->subscriptionManager->cancelScheduledDowngrade($subscription);
            $this->addFlash('success', 'Scheduled plan change cancelled.');
        } catch (PaymentIntegrationException $e) {
            $this->addFlash('error', sprintf('Could not cancel the scheduled change: %s', $e->getMessage()));
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
