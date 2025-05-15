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

use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;

class Builder extends AuthenticatedMenu
{
    public function systemMenu(ItemInterface $menu): void
    {
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
