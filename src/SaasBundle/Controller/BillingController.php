<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\Controller;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

class BillingController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function __invoke(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $company = $this->companySelector->getCompany();
        assert($company instanceof Ulid);

        $subscription = $this->subscriptionManager->getSubscriptionFor($this->companyRepository->find($company));

        if (! $subscription instanceof Subscription || null === $subscription->getSubscriptionId()) {
            $this->addFlash('error', 'No subscription found');
            return $this->redirectToRoute('_dashboard');
        }

        return $this->redirect($this->subscriptionManager->getCustomerPortalUrl($subscription));
    }
}
