<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\MenuItem as BaseItem;

class MenuItem extends BaseItem
{
    public function addDivider($type = '')
    {
        $name = uniqid();

        if (!empty($type)) {
            $type = '-' . $type;
        }

        $child = $this->addChild($name, array('extras' => array('divider' => $type)));

        return $child;
    }

    public function isDivider()
    {
        return $this->getExtra('divider') !== null;
    }
}
