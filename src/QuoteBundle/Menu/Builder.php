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

namespace SolidInvoice\QuoteBundle\Menu;

use InvalidArgumentException;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;

/**
 * Menu items for quotes.
 */
class Builder extends AuthenticatedMenu
{
    /**
     * Menu builder for the quotes index.
     *
     * @throws InvalidArgumentException
     */
    public function sidebar(ItemInterface $menu): void
    {
        $menu->addHeader('quotes');
        $menu->addChild(QuoteMenu::list());
        $menu->addChild(QuoteMenu::create());

        $menu->addDivider();
    }
}
