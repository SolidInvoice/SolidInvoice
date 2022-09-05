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

namespace SolidInvoice\PaymentBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;

class Builder extends AuthenticatedMenu
{
    /**
     * Renders the top menu for payments.
     */
    public function topMenu(ItemInterface $menu): void
    {
        $menu->addHeader('Payments');
        $menu->addChild(PaymentMenu::main());
        $menu->addChild(PaymentMenu::methods());
    }
}
