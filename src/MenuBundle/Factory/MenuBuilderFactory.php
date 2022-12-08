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

namespace SolidInvoice\MenuBundle\Factory;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

final class MenuBuilderFactory
{
    public function build(FactoryInterface $factory): ItemInterface
    {
        return $factory->createItem('root');
    }
}
