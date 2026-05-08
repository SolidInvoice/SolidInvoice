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
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

final class SelectPlanAction extends AbstractController
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly SubscriptionProviderInterface $subscriptionProvider,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function __invoke(): Response
    {
        $subscription = $this->getSubscription();

        if ($subscription instanceof Subscription && $subscription->getStatus() === SubscriptionStatus::ACTIVE) {
            return $this->redirectToRoute('billing_index');
        }

        $plans = $this->planRepository->findAllOrdered();

        if ($plans === []) {
            $this->addFlash('error', 'No subscription plans are available.');

            return $this->redirectToRoute('_dashboard');
        }

        if (count($plans) === 1) {
            return $this->redirectToRoute('saas_subscription_checkout');
        }

        return $this->render('@SolidInvoiceSaas/subscription/pricing.html.twig', [
            'plans' => $plans,
            'subscription' => $subscription,
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
