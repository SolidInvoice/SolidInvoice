<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\Menu;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Builder extends AuthenticatedMenu
{
    public function __construct(
        private readonly CompanySelector $companySelector,
        private readonly CompanyRepository $companyRepository,
        private readonly SubscriptionManager $subscriptionManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($authorizationChecker);
    }

    public function systemMenu(ItemInterface $menu): void
    {
        $subscription = $this->subscriptionManager->getSubscriptionFor(
            $this->companyRepository->find($this->companySelector->getCompany())
        );

        if ($subscription === null || null === $subscription->getSubscriptionId()) {
            return;
        }

        $menu->addChild(
            'billing',
            [
                'label' => 'Subscription',
                'route' => 'billing_index',
                'extras' => ['icon' => 'credit-card'],
            ],
        );
    }
}
