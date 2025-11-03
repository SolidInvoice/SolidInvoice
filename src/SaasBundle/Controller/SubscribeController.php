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
use SolidWorx\Platform\SaasBundle\Integration\Options;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Uid\Ulid;

class SubscribeController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $subscription = $this->getSubscription();
        if (! $subscription instanceof Subscription) {
            $this->addFlash('error', 'No subscription found');
            return $this->redirectToRoute('_dashboard');
        }

        $options = Options::new()
            ->withEmail($user->getEmail())
            // @TODO: If status is trial, and we want to allow the trial to be extended, skipTrial should be false.
            ->withSkipTrial(true);

        $checkoutUrl = $this->subscriptionManager
            ->getCheckoutUrl($subscription, $options);

        return $this->redirect($checkoutUrl);
    }

    private function getSubscription(): ?Subscription
    {
        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return null;
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company) {
            return null;
        }

        return $this->subscriptionManager->getSubscriptionFor($company);
    }
}
