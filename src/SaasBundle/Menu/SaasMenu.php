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

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;

final class SaasMenu
{
    public function __construct(
        private readonly CompanySelector $companySelector,
        private readonly CompanyRepository $companyRepository,
        private readonly SubscriptionManager $subscriptionManager,
    ) {
    }

    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_SYSTEM->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $systemMenu = $menu->getChild('menu.top.system');

        if (! $systemMenu instanceof ItemInterface) {
            return;
        }

        $subscription = $this->subscriptionManager->getSubscriptionFor(
            $this->companyRepository->find($this->companySelector->getCompany())
        );

        if (null === $subscription) {
            return;
        }

        $systemMenu->addChild(
            'billing',
            [
                'label' => 'Subscription',
                'route' => 'billing_index',
                'extras' => ['icon' => 'receipt-2'],
            ],
        );
    }
}
